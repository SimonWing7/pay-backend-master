<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Mail\BillingChargeResultMail;
use App\Mail\BillingStatementMail;
use App\Models\AppUserPayment;
use App\Models\BillingStatement;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\MerchantBillingCard;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.enabled' => true,
            'telr.store_id' => '29840',
            'telr.auth_key' => 'remote-secret',
            'telr.order_auth_key' => 'order-secret',
            'telr.test_mode' => true,
            'telr.order_url' => 'https://telr.test/order.json',
            'telr.remote_url' => 'https://telr.test/remote.xml',
        ]);

        Mail::fake();
        // 06:00 Dubai on 1 Nov 2026 — when the monthly statement run happens.
        Carbon::setTestNow(Carbon::parse('2026-11-01 02:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -- helpers ---------------------------------------------------------

    private function merchant(array $overrides = []): Merchant
    {
        static $n = 0;
        $n++;

        return Merchant::create(array_merge([
            'name' => "Merchant {$n}",
            'email' => "merchant{$n}@example.com",
            'password' => 'password123',
            'is_active' => true,
            'must_change_password' => false,
            'fee_percentage' => 1.5,
            'billing_enabled' => true,
        ], $overrides));
    }

    private function payment(Merchant $merchant, float $amount, string $createdAtUtc, PaymentStatus $status = PaymentStatus::Complete): AppUserPayment
    {
        $invoice = Invoice::create(['merchant_id' => $merchant->id, 'total_fee' => $amount, 'status' => InvoiceStatus::Paid]);

        $payment = AppUserPayment::create([
            'invoice_id' => $invoice->id,
            'payment_channel' => 'web',
            'status' => $status,
            'token' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        $payment->forceFill(['created_at' => $createdAtUtc, 'updated_at' => $createdAtUtc])->saveQuietly();

        return $payment;
    }

    private function activeCard(Merchant $merchant, string $ref = '030130190634'): MerchantBillingCard
    {
        return MerchantBillingCard::create([
            'merchant_id' => $merchant->id,
            'telr_tran_ref' => $ref,
            'card_type' => 'Visa Credit',
            'last4' => '1111',
            'expiry_month' => 12,
            'expiry_year' => 2030,
            'status' => 'active',
            'setup_cartid' => 'EPCARD-' . $merchant->id . '-' . uniqid(),
        ]);
    }

    /** A merchant with a billable October 2026: AED 10,000 in completed payments, so a 150.00 fee. */
    private function octoberStatement(?Merchant $merchant = null, bool $withCard = true): BillingStatement
    {
        $merchant ??= $this->merchant();
        $this->payment($merchant, 6000, '2026-10-10 08:00:00');
        $this->payment($merchant, 4000, '2026-10-20 08:00:00');
        if ($withCard) {
            $this->activeCard($merchant);
        }

        return app(BillingService::class)->generateStatement($merchant, Carbon::parse('2026-10-15', 'Asia/Dubai'));
    }

    private function telrXml(string $status, string $message = 'Authorised', string $tranref = '030130193252', string $code = '9792471'): string
    {
        return '<?xml version="1.0"?><remote><auth><status>' . $status . '</status><code>' . $code . '</code><message>'
            . $message . '</message><tranref>' . $tranref . '</tranref></auth></remote>';
    }

    // -- amounts and periods ---------------------------------------------

    public function test_fee_is_rounded_to_two_decimals(): void
    {
        $billing = app(BillingService::class);

        $this->assertSame(150.0, $billing->feeFor(10000, 1.5));
        $this->assertSame(185.19, $billing->feeFor(12345.67, 1.5));
        $this->assertSame(0.0, $billing->feeFor(0, 1.5));
    }

    public function test_volume_uses_dubai_month_boundaries_and_only_completed_payments_of_that_merchant(): void
    {
        $merchant = $this->merchant();
        $other = $this->merchant();

        // 00:30 Dubai on 1 Oct = 20:30 UTC on 30 Sep -> October
        $this->payment($merchant, 100, '2026-09-30 20:30:00');
        // 23:30 Dubai on 31 Oct = 19:30 UTC on 31 Oct -> October
        $this->payment($merchant, 200, '2026-10-31 19:30:00');
        // 00:30 Dubai on 1 Nov = 20:30 UTC on 31 Oct -> November, NOT October
        $this->payment($merchant, 400, '2026-10-31 20:30:00');
        // not completed / someone else's -> never counted
        $this->payment($merchant, 800, '2026-10-15 10:00:00', PaymentStatus::Failed);
        $this->payment($merchant, 1600, '2026-10-15 10:00:00', PaymentStatus::Abandoned);
        $this->payment($other, 3200, '2026-10-15 10:00:00');

        $billing = app(BillingService::class);
        [$start, $end] = $billing->monthBounds(Carbon::parse('2026-10-15', 'Asia/Dubai'));

        $this->assertSame(300.0, $billing->volumeFor($merchant, $start, $end));
    }

    // -- statements ------------------------------------------------------

    public function test_statement_is_generated_with_the_right_figures_and_dates(): void
    {
        $statement = $this->octoberStatement();

        $this->assertSame('issued', $statement->status);
        $this->assertSame('10000.00', $statement->volume);
        $this->assertSame('1.50', $statement->fee_percentage);
        $this->assertSame('150.00', $statement->fee_amount);
        $this->assertSame('2026-10-01', $statement->period_start->toDateString());
        $this->assertSame('2026-10-31', $statement->period_end->toDateString());
        $this->assertSame(sprintf('EP-202610-%04d', $statement->merchant_id), $statement->number);
        // 09:00 Dubai on 3 Nov = 05:00 UTC
        $this->assertSame('2026-11-03 05:00:00', $statement->charge_due_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-03 05:00:00', $statement->next_attempt_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_generating_twice_never_creates_a_second_statement(): void
    {
        $merchant = $this->merchant();
        $this->payment($merchant, 5000, '2026-10-10 08:00:00');
        $billing = app(BillingService::class);
        $month = Carbon::parse('2026-10-15', 'Asia/Dubai');

        $first = $billing->generateStatement($merchant, $month);
        $second = $billing->generateStatement($merchant, $month);

        $this->assertTrue($first->wasRecentlyCreated);
        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, BillingStatement::count());
    }

    public function test_a_later_rate_change_does_not_alter_an_issued_statement(): void
    {
        $statement = $this->octoberStatement();
        $statement->merchant->update(['fee_percentage' => 5]);

        $again = app(BillingService::class)->generateStatement($statement->merchant->fresh(), Carbon::parse('2026-10-15', 'Asia/Dubai'));

        $this->assertSame('150.00', $again->fee_amount);
    }

    public function test_a_month_still_in_progress_is_never_billed(): void
    {
        $merchant = $this->merchant();
        $this->payment($merchant, 5000, '2026-11-01 00:30:00'); // 1 Nov, current month at the frozen time
        $billing = app(BillingService::class);

        // Partway through November
        Carbon::setTestNow(Carbon::parse('2026-11-14 08:00:00', 'UTC'));
        $this->assertNull($billing->generateStatement($merchant, Carbon::parse('2026-11-14', 'Asia/Dubai')));
        $this->assertSame(0, BillingStatement::count());

        // The very last day of the month is still in progress
        Carbon::setTestNow(Carbon::parse('2026-11-30 08:00:00', 'UTC'));
        $this->assertNull($billing->generateStatement($merchant, Carbon::parse('2026-11-30', 'Asia/Dubai')));

        // Once it has ended (1 Dec, Dubai) it can be billed
        Carbon::setTestNow(Carbon::parse('2026-11-30 20:30:00', 'UTC'));
        $this->assertNotNull($billing->generateStatement($merchant, Carbon::parse('2026-11-14', 'Asia/Dubai')));
    }

    public function test_generate_command_refuses_a_month_in_progress_but_allows_a_dry_run_preview(): void
    {
        $merchant = $this->merchant();
        $this->payment($merchant, 5000, '2026-11-01 00:30:00');
        Carbon::setTestNow(Carbon::parse('2026-11-14 08:00:00', 'UTC'));

        $this->artisan('billing:generate --period=2026-11')->assertExitCode(1);
        $this->assertSame(0, BillingStatement::count());

        $this->artisan('billing:generate --period=2026-11 --dry-run')->assertExitCode(0);
        $this->assertSame(0, BillingStatement::count());
        Mail::assertNothingSent();
    }

    public function test_months_with_no_fee_produce_no_statement(): void
    {
        $merchant = $this->merchant();

        $this->assertNull(app(BillingService::class)->generateStatement($merchant, Carbon::parse('2026-10-15', 'Asia/Dubai')));
        $this->assertSame(0, BillingStatement::count());
    }

    // -- charging --------------------------------------------------------

    public function test_a_due_statement_is_charged_to_the_saved_card_and_marked_paid(): void
    {
        $statement = $this->octoberStatement();
        Http::fake(['telr.test/remote.xml' => Http::response($this->telrXml('A'), 200)]);
        Carbon::setTestNow(Carbon::parse('2026-11-03 05:01:00', 'UTC'));

        $result = app(BillingService::class)->charge($statement);

        $this->assertSame('paid', $result);
        $statement->refresh();
        $this->assertSame('paid', $statement->status);
        $this->assertSame('030130193252', $statement->telr_tran_ref);
        $this->assertSame(1, $statement->attempts);
        $this->assertSame('authorised', $statement->attemptsLog->first()->outcome);

        Http::assertSent(fn ($r) => str_contains($r->body(), '<class>cont</class>')
            && str_contains($r->body(), '<ref>030130190634</ref>')
            && str_contains($r->body(), '<amount>150.00</amount>')
            && str_contains($r->body(), '<cartid>' . $statement->number . '-A1</cartid>'));
        Mail::assertSent(BillingChargeResultMail::class, fn ($m) => $m->kind === 'paid');
    }

    public function test_nothing_is_charged_before_the_due_date(): void
    {
        $statement = $this->octoberStatement();
        Http::fake();

        $this->assertSame('skipped', app(BillingService::class)->charge($statement));
        Http::assertNothingSent();
        $this->assertSame('issued', $statement->fresh()->status);
    }

    public function test_a_paid_statement_can_never_be_charged_again(): void
    {
        $statement = $this->octoberStatement();
        Http::fake(['telr.test/remote.xml' => Http::response($this->telrXml('A'), 200)]);
        $billing = app(BillingService::class);

        $this->assertSame('paid', $billing->charge($statement, true));
        $this->assertSame('skipped', $billing->charge($statement->fresh(), true));
        $this->assertSame('skipped', $billing->charge($statement->fresh(), true));

        Http::assertSentCount(1);
        $this->assertSame(1, $statement->fresh()->attemptsLog()->count());
    }

    public function test_a_statement_already_being_charged_is_not_charged_by_a_second_run(): void
    {
        $statement = $this->octoberStatement();
        $statement->update(['status' => 'charging']);
        Http::fake();

        $this->assertSame('skipped', app(BillingService::class)->charge($statement, true));
        Http::assertNothingSent();
    }

    public function test_without_a_card_it_waits_and_only_notifies_once(): void
    {
        $statement = $this->octoberStatement(null, false);
        Http::fake(['telr.test/remote.xml' => Http::response($this->telrXml('A'), 200)]);
        Log::spy();
        $billing = app(BillingService::class);

        Carbon::setTestNow(Carbon::parse('2026-11-03 05:01:00', 'UTC'));
        $this->assertSame('awaiting_card', $billing->charge($statement));
        $this->assertSame('awaiting_card', $statement->fresh()->status);

        // Next day, still no card: re-checked silently
        Carbon::setTestNow(Carbon::parse('2026-11-04 05:01:00', 'UTC'));
        $this->assertSame('awaiting_card', $billing->charge($statement->fresh()));

        Http::assertNothingSent();
        Mail::assertSent(BillingChargeResultMail::class, 1);
        Log::shouldHaveReceived('error')->once();

        // Merchant adds a card; the next daily run charges it
        $this->activeCard($statement->merchant);
        Carbon::setTestNow(Carbon::parse('2026-11-05 05:01:00', 'UTC'));
        $this->assertSame('paid', $billing->charge($statement->fresh()));
        Http::assertSentCount(1);
    }

    public function test_a_declined_card_is_retried_twice_then_fails_and_alerts(): void
    {
        $statement = $this->octoberStatement();
        Http::fake(['telr.test/remote.xml' => Http::response($this->telrXml('D', 'Insufficient Funds', '', '41'), 200)]);
        Log::spy();
        $billing = app(BillingService::class);

        // Attempt 1 on the 3rd -> retry on the 6th (3 days after the first failure)
        Carbon::setTestNow(Carbon::parse('2026-11-03 05:01:00', 'UTC'));
        $this->assertSame('retry_scheduled', $billing->charge($statement));
        $statement->refresh();
        $this->assertSame('issued', $statement->status);
        $this->assertSame(1, $statement->attempts);
        $this->assertSame('2026-11-06 05:00:00', $statement->next_attempt_at->utc()->format('Y-m-d H:i:s'));

        // Not due yet on the 5th
        Carbon::setTestNow(Carbon::parse('2026-11-05 05:01:00', 'UTC'));
        $this->assertSame('skipped', $billing->charge($statement->fresh()));

        // Attempt 2 on the 6th -> retry on the 10th (7 days after the first failure)
        Carbon::setTestNow(Carbon::parse('2026-11-06 05:01:00', 'UTC'));
        $this->assertSame('retry_scheduled', $billing->charge($statement->fresh()));
        $this->assertSame('2026-11-10 05:00:00', $statement->fresh()->next_attempt_at->utc()->format('Y-m-d H:i:s'));

        // Attempt 3 on the 10th -> give up
        Carbon::setTestNow(Carbon::parse('2026-11-10 05:01:00', 'UTC'));
        $this->assertSame('failed', $billing->charge($statement->fresh()));
        $statement->refresh();
        $this->assertSame('failed', $statement->status);
        $this->assertSame(3, $statement->attempts);
        $this->assertNull($statement->next_attempt_at);
        $this->assertSame('Insufficient Funds', $statement->failure_message);

        Http::assertSentCount(3);
        Log::shouldHaveReceived('error')->withArgs(fn ($m) => str_contains($m, 'all charge attempts failed'))->once();
        Mail::assertSent(BillingChargeResultMail::class, fn ($m) => $m->kind === 'retry');
        Mail::assertSent(BillingChargeResultMail::class, fn ($m) => $m->kind === 'failed');

        // A failed statement is never picked up again by the schedule
        $this->assertCount(0, $billing->dueStatements());
    }

    public function test_an_unclear_telr_answer_goes_to_review_and_is_never_auto_retried(): void
    {
        $statement = $this->octoberStatement();
        Http::fake(['telr.test/remote.xml' => Http::response('Gateway Timeout', 504)]);
        Log::spy();
        $billing = app(BillingService::class);

        Carbon::setTestNow(Carbon::parse('2026-11-03 05:01:00', 'UTC'));
        $this->assertSame('needs_review', $billing->charge($statement));

        $statement->refresh();
        $this->assertSame('needs_review', $statement->status);
        $this->assertNull($statement->next_attempt_at);
        $this->assertCount(0, $billing->dueStatements());

        Carbon::setTestNow(Carbon::parse('2026-11-20 05:01:00', 'UTC'));
        $this->assertSame('skipped', $billing->charge($statement->fresh()));
        Http::assertSentCount(1);
        Mail::assertNotSent(BillingChargeResultMail::class);
        Log::shouldHaveReceived('error')->withArgs(fn ($m) => str_contains($m, 'outcome unknown'));
    }

    public function test_a_charge_stuck_mid_flight_is_moved_to_review_not_retried(): void
    {
        $statement = $this->octoberStatement();
        $statement->update(['status' => 'charging']);
        DB::table('billing_statements')->where('id', $statement->id)->update(['updated_at' => now()->subHour()]);
        Http::fake();

        $this->assertSame(1, app(BillingService::class)->recoverStaleCharges());

        $this->assertSame('needs_review', $statement->fresh()->status);
        $this->assertCount(0, app(BillingService::class)->dueStatements());
    }

    public function test_admin_actions(): void
    {
        $billing = app(BillingService::class);

        $paid = $this->octoberStatement();
        $billing->markPaidManually($paid, 'Paid by bank transfer');
        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame('manual', $paid->fresh()->paid_method);
        $this->assertFalse($billing->void($paid->fresh()));

        $other = $this->octoberStatement();
        $this->assertTrue($billing->void($other));
        $this->assertSame('void', $other->fresh()->status);
        $this->assertSame('skipped', $billing->charge($other->fresh(), true));

        // Retry-now resets a failed statement and charges immediately
        $failed = $this->octoberStatement();
        $failed->update(['status' => 'failed', 'attempts' => 3]);
        Http::fake(['telr.test/remote.xml' => Http::response($this->telrXml('A'), 200)]);
        $this->assertSame('paid', $billing->retryNow($failed->fresh()));
        $this->assertSame(1, $failed->fresh()->attempts);
        $this->assertSame('skipped', $billing->retryNow($failed->fresh()));
    }

    // -- card registration -----------------------------------------------

    public function test_card_registration_records_consent_and_returns_the_telr_url(): void
    {
        $merchant = $this->merchant();
        Http::fake(['telr.test/order.json' => Http::response(['order' => ['ref' => 'ORD123', 'url' => 'https://secure.telr.com/pay?o=ORD123']], 200)]);

        $result = app(BillingService::class)->startCardSetup($merchant, '203.0.113.9');

        $this->assertTrue($result['success']);
        $this->assertSame('https://secure.telr.com/pay?o=ORD123', $result['url']);
        $card = $merchant->billingCards()->first();
        $this->assertSame('pending', $card->status);
        $this->assertSame('ORD123', $card->telr_order_ref);
        $this->assertSame('203.0.113.9', $card->consent_ip);
        $this->assertNotNull($card->consent_at);
        Http::assertSent(fn ($r) => $r['order']['amount'] === '1.00' && str_contains($r['return']['authorised'], $card->setup_cartid));
    }

    public function test_card_registration_failure_is_recorded_and_reported(): void
    {
        $merchant = $this->merchant();
        Http::fake(['telr.test/order.json' => Http::response(['error' => ['message' => 'E01:Invalid request']], 200)]);

        $result = app(BillingService::class)->startCardSetup($merchant, '203.0.113.9');

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $merchant->billingCards()->first()->status);
    }

    public function test_completing_card_registration_activates_the_card_encrypts_the_reference_and_refunds(): void
    {
        $merchant = $this->merchant();
        $old = $this->activeCard($merchant, 'OLDREF');
        $card = MerchantBillingCard::create([
            'merchant_id' => $merchant->id, 'status' => 'pending',
            'setup_cartid' => 'EPCARD-NEW', 'telr_order_ref' => 'ORD123', 'consent_at' => now(),
        ]);
        Http::fake([
            'telr.test/order.json' => Http::response(['order' => [
                'status' => ['code' => 3, 'text' => 'Paid'],
                'transaction' => ['ref' => '030130190634', 'status' => 'A'],
                'card' => ['type' => 'Visa Credit', 'last4' => '1111', 'expiry' => ['month' => 12, 'year' => 2030]],
            ]], 200),
            'telr.test/remote.xml' => Http::response($this->telrXml('A', 'Accepted', '030130193357'), 200),
        ]);

        $this->assertSame('activated', app(BillingService::class)->completeCardSetup($card));

        $card->refresh();
        $this->assertSame('active', $card->status);
        $this->assertSame('030130190634', $card->telr_tran_ref);
        $this->assertSame('1111', $card->last4);
        $this->assertNotNull($card->verification_refunded_at);
        $this->assertSame('replaced', $old->fresh()->status);
        $this->assertSame($card->id, $merchant->fresh()->activeBillingCard->id);

        // The reference is encrypted at rest and never serialised
        $this->assertNotSame('030130190634', DB::table('merchant_billing_cards')->where('id', $card->id)->value('telr_tran_ref'));
        $this->assertArrayNotHasKey('telr_tran_ref', $card->toArray());
        Http::assertSent(fn ($r) => str_contains($r->body(), '<type>refund</type>') && str_contains($r->body(), '<ref>030130190634</ref>') && str_contains($r->body(), '<amount>1.00</amount>'));

        // Completing again does nothing (no second refund)
        $this->assertSame('already_active', app(BillingService::class)->completeCardSetup($card->fresh()));
        Http::assertSentCount(2);
    }

    public function test_cancelled_or_declined_registration_does_not_create_a_card(): void
    {
        $merchant = $this->merchant();
        $card = MerchantBillingCard::create([
            'merchant_id' => $merchant->id, 'status' => 'pending',
            'setup_cartid' => 'EPCARD-X', 'telr_order_ref' => 'ORD1', 'consent_at' => now(),
        ]);
        Http::fake(['telr.test/order.json' => Http::response(['order' => ['status' => ['code' => -2, 'text' => 'Cancelled']]], 200)]);

        $this->assertSame('declined', app(BillingService::class)->completeCardSetup($card));
        $this->assertSame('failed', $card->fresh()->status);
        $this->assertNull($merchant->fresh()->activeBillingCard);
    }

    public function test_registration_still_pending_stays_pending(): void
    {
        $merchant = $this->merchant();
        $card = MerchantBillingCard::create([
            'merchant_id' => $merchant->id, 'status' => 'pending',
            'setup_cartid' => 'EPCARD-P', 'telr_order_ref' => 'ORD1', 'consent_at' => now(),
        ]);
        Http::fake(['telr.test/order.json' => Http::response(['order' => ['status' => ['code' => 1, 'text' => 'Pending']]], 200)]);

        $this->assertSame('pending', app(BillingService::class)->completeCardSetup($card));
        $this->assertSame('pending', $card->fresh()->status);
    }

    public function test_a_failed_verification_refund_does_not_lose_the_card_but_is_logged(): void
    {
        $merchant = $this->merchant();
        $card = MerchantBillingCard::create([
            'merchant_id' => $merchant->id, 'status' => 'pending',
            'setup_cartid' => 'EPCARD-R', 'telr_order_ref' => 'ORD1', 'consent_at' => now(),
        ]);
        Http::fake([
            'telr.test/order.json' => Http::response(['order' => [
                'status' => ['code' => 3, 'text' => 'Paid'],
                'transaction' => ['ref' => '030130190634', 'status' => 'A'],
                'card' => ['type' => 'Visa Credit', 'last4' => '1111', 'expiry' => ['month' => 12, 'year' => 2030]],
            ]], 200),
            'telr.test/remote.xml' => Http::response($this->telrXml('D', 'Declined'), 200),
        ]);
        Log::spy();

        $this->assertSame('activated', app(BillingService::class)->completeCardSetup($card));
        $this->assertSame('active', $card->fresh()->status);
        $this->assertNull($card->fresh()->verification_refunded_at);
        Log::shouldHaveReceived('error')->withArgs(fn ($m) => str_contains($m, 'not refunded'));
    }

    // -- commands --------------------------------------------------------

    public function test_billing_commands_do_nothing_while_the_master_switch_is_off(): void
    {
        config(['billing.enabled' => false]);
        $merchant = $this->merchant();
        $this->payment($merchant, 5000, '2026-10-10 08:00:00');
        Http::fake();

        $this->artisan('billing:generate')->assertExitCode(0);
        $this->artisan('billing:charge')->assertExitCode(0);

        $this->assertSame(0, BillingStatement::count());
        Http::assertNothingSent();
    }

    public function test_generate_command_bills_only_enabled_merchants_and_emails_once(): void
    {
        $billed = $this->merchant();
        $notEnabled = $this->merchant(['billing_enabled' => false]);
        $inactive = $this->merchant(['is_active' => false]);
        foreach ([$billed, $notEnabled, $inactive] as $m) {
            $this->payment($m, 5000, '2026-10-10 08:00:00');
        }

        $this->artisan('billing:generate')->assertExitCode(0);
        $this->artisan('billing:generate')->assertExitCode(0);

        $this->assertSame(1, BillingStatement::count());
        $this->assertSame($billed->id, BillingStatement::first()->merchant_id);
        Mail::assertSent(BillingStatementMail::class, 1);
    }

    public function test_generate_command_dry_run_creates_nothing(): void
    {
        $merchant = $this->merchant();
        $this->payment($merchant, 5000, '2026-10-10 08:00:00');

        $this->artisan('billing:generate --dry-run')->assertExitCode(0);

        $this->assertSame(0, BillingStatement::count());
        Mail::assertNothingSent();
    }

    public function test_charge_command_only_charges_what_is_due_and_now_requires_a_statement(): void
    {
        $statement = $this->octoberStatement();
        Http::fake(['telr.test/remote.xml' => Http::response($this->telrXml('A'), 200)]);

        $this->artisan('billing:charge --now')->assertExitCode(1);

        // Before the due date: nothing happens
        $this->artisan('billing:charge')->assertExitCode(0);
        Http::assertNothingSent();

        Carbon::setTestNow(Carbon::parse('2026-11-03 05:01:00', 'UTC'));
        $this->artisan('billing:charge')->assertExitCode(0);
        $this->assertSame('paid', $statement->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_charge_command_accepts_a_statement_by_number_or_id(): void
    {
        Http::fake(['telr.test/remote.xml' => Http::response($this->telrXml('A'), 200)]);

        $byNumber = $this->octoberStatement();
        $this->artisan('billing:charge --statement=' . $byNumber->number . ' --now')->assertExitCode(0);
        $this->assertSame('paid', $byNumber->fresh()->status);

        $byId = $this->octoberStatement();
        $this->artisan('billing:charge --statement=' . $byId->id . ' --now')->assertExitCode(0);
        $this->assertSame('paid', $byId->fresh()->status);

        $this->artisan('billing:charge --statement=EP-999999-9999 --now')->assertExitCode(1);
    }

    // -- merchant pages --------------------------------------------------

    public function test_merchant_can_see_their_billing_page_and_statement_but_not_someone_elses(): void
    {
        $statement = $this->octoberStatement();
        $stranger = $this->merchant();

        $this->actingAs($statement->merchant, 'merchants')
            ->get(route('merchant.billing.index'))
            ->assertOk()
            ->assertSee($statement->number)
            ->assertSee('Visa Credit ending 1111')
            ->assertSee('Monthly charges authorised');

        $this->actingAs($statement->merchant, 'merchants')
            ->get(route('merchant.billing.statement', $statement->number))
            ->assertOk()
            ->assertSee('150.00');

        $this->actingAs($stranger, 'merchants')
            ->get(route('merchant.billing.statement', $statement->number))
            ->assertNotFound();
    }

    public function test_adding_a_card_requires_the_consent_tick(): void
    {
        $merchant = $this->merchant();
        Http::fake();

        $this->actingAs($merchant, 'merchants')
            ->post(route('merchant.billing.card.start'))
            ->assertSessionHasErrors('consent');

        Http::assertNothingSent();
        $this->assertSame(0, $merchant->billingCards()->count());
    }

    public function test_card_return_only_works_for_the_merchants_own_registration(): void
    {
        $owner = $this->merchant();
        $stranger = $this->merchant();
        $card = MerchantBillingCard::create([
            'merchant_id' => $owner->id, 'status' => 'pending',
            'setup_cartid' => 'EPCARD-OWN', 'telr_order_ref' => 'ORD1', 'consent_at' => now(),
        ]);

        $this->actingAs($stranger, 'merchants')
            ->get(route('merchant.billing.card.return', 'EPCARD-OWN'))
            ->assertNotFound();
    }
}
