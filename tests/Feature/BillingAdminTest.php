<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\AppUserPayment;
use App\Models\BillingStatement;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\MerchantBillingCard;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BillingAdminTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.enabled' => true,
            'telr.store_id' => '29840', 'telr.auth_key' => 'k', 'telr.order_auth_key' => 'k',
            'telr.remote_url' => 'https://telr.test/remote.xml',
        ]);
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-01 02:00:00', 'UTC'));

        $this->admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password123']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function merchant(array $overrides = []): Merchant
    {
        static $n = 0;
        $n++;

        return Merchant::create(array_merge([
            'name' => "Admin Test Merchant {$n}", 'email' => "am{$n}@example.com", 'password' => 'password123',
            'is_active' => true, 'must_change_password' => false, 'fee_percentage' => 1.5, 'billing_enabled' => true,
        ], $overrides));
    }

    private function completedPayment(Merchant $merchant, float $amount, string $createdAt): void
    {
        $invoice = Invoice::create(['merchant_id' => $merchant->id, 'total_fee' => $amount, 'status' => InvoiceStatus::Paid]);
        $payment = AppUserPayment::create([
            'invoice_id' => $invoice->id, 'payment_channel' => 'web', 'status' => PaymentStatus::Complete,
            'token' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        $payment->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
    }

    private function statement(Merchant $merchant, string $status = 'issued'): BillingStatement
    {
        $this->completedPayment($merchant, 10000, '2026-10-10 08:00:00');
        MerchantBillingCard::create([
            'merchant_id' => $merchant->id, 'telr_tran_ref' => '030130190634', 'status' => 'active',
            'card_type' => 'Visa Credit', 'last4' => '1111', 'expiry_month' => 12, 'expiry_year' => 2030,
            'setup_cartid' => 'EPCARD-' . uniqid(),
        ]);
        $statement = app(BillingService::class)->generateStatement($merchant, Carbon::parse('2026-10-15', 'Asia/Dubai'));
        $statement->update(['status' => $status]);

        return $statement;
    }

    private function telrOk(): void
    {
        Http::fake(['telr.test/remote.xml' => Http::response('<remote><auth><status>A</status><code>1</code><message>Authorised</message><tranref>030130193252</tranref></auth></remote>', 200)]);
    }

    public function test_admin_can_see_the_billing_page_filtered_by_status(): void
    {
        $paid = $this->statement($this->merchant(['name' => 'Paid Co']), 'paid');
        $failed = $this->statement($this->merchant(['name' => 'Failed Co']), 'failed');

        $this->actingAs($this->admin, 'admin')->get(route('admin.billing.index'))
            ->assertOk()->assertSee($paid->number)->assertSee($failed->number)->assertSee('need attention');

        // (Merchant names also appear in the filter dropdown, so check by statement number.)
        $this->actingAs($this->admin, 'admin')->get(route('admin.billing.index', ['status' => 'failed']))
            ->assertOk()->assertSee($failed->number)->assertDontSee($paid->number);
    }

    public function test_retrying_a_statement_under_review_requires_confirming_nothing_was_charged(): void
    {
        $statement = $this->statement($this->merchant(), 'needs_review');
        $this->telrOk();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.billing.retry', $statement->id))
            ->assertSessionHasErrors('confirmed');

        Http::assertNothingSent();
        $this->assertSame('needs_review', $statement->fresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.billing.retry', $statement->id), ['confirmed' => '1'])
            ->assertSessionHas('success');

        Http::assertSentCount(1);
        $this->assertSame('paid', $statement->fresh()->status);
    }

    public function test_retrying_a_failed_statement_charges_the_card(): void
    {
        $statement = $this->statement($this->merchant(), 'failed');
        $statement->update(['attempts' => 3]);
        $this->telrOk();

        $this->actingAs($this->admin, 'admin')->post(route('admin.billing.retry', $statement->id))->assertSessionHas('success');

        $this->assertSame('paid', $statement->fresh()->status);
    }

    public function test_marking_paid_needs_a_note_and_records_who_did_it(): void
    {
        $statement = $this->statement($this->merchant(), 'failed');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.billing.paid', $statement->id))
            ->assertSessionHasErrors('note');
        $this->assertSame('failed', $statement->fresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.billing.paid', $statement->id), ['note' => 'Bank transfer ref 123'])
            ->assertSessionHas('success');

        $statement->refresh();
        $this->assertSame('paid', $statement->status);
        $this->assertSame('manual', $statement->paid_method);
        $this->assertStringContainsString('Test Admin', $statement->failure_message);
        $this->assertStringContainsString('Bank transfer ref 123', $statement->failure_message);
    }

    public function test_a_paid_statement_cannot_be_voided_or_marked_paid_again(): void
    {
        $statement = $this->statement($this->merchant(), 'paid');

        $this->actingAs($this->admin, 'admin')->post(route('admin.billing.void', $statement->id))->assertSessionHas('error');
        $this->actingAs($this->admin, 'admin')->post(route('admin.billing.paid', $statement->id), ['note' => 'x'])->assertSessionHas('error');

        $this->assertSame('paid', $statement->fresh()->status);
    }

    public function test_admin_can_set_a_merchants_rate_and_switch_billing_on_and_off(): void
    {
        $merchant = $this->merchant(['billing_enabled' => false]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.merchants.update', $merchant->id), [
                'name' => $merchant->name, 'email' => $merchant->email, 'fee_percentage' => '2.25', 'billing_enabled' => '1',
            ])->assertSessionHasNoErrors();

        $merchant->refresh();
        $this->assertSame('2.25', $merchant->fee_percentage);
        $this->assertTrue($merchant->billing_enabled);

        // Unticked checkbox isn't submitted at all -> off
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.merchants.update', $merchant->id), [
                'name' => $merchant->name, 'email' => $merchant->email, 'fee_percentage' => '2.25',
            ])->assertSessionHasNoErrors();

        $this->assertFalse($merchant->fresh()->billing_enabled);
    }

    public function test_invalid_rates_are_rejected(): void
    {
        $merchant = $this->merchant();

        foreach (['-1', '101', 'abc'] as $bad) {
            $this->actingAs($this->admin, 'admin')
                ->put(route('admin.merchants.update', $merchant->id), ['name' => $merchant->name, 'email' => $merchant->email, 'fee_percentage' => $bad])
                ->assertSessionHasErrors('fee_percentage');
        }

        $this->assertSame('1.50', $merchant->fresh()->fee_percentage);
    }

    public function test_admin_merchant_page_shows_revenue_at_the_merchants_own_rate_and_billing_details(): void
    {
        $merchant = $this->merchant(['fee_percentage' => 2]);
        $statement = $this->statement($merchant, 'paid');

        $this->actingAs($this->admin, 'admin')->get(route('admin.merchants.show', $merchant->id))
            ->assertOk()
            ->assertSee('AED 200.00')                // 2% of 10,000, not the old hardcoded 1%
            ->assertSee('Visa Credit ending 1111')
            ->assertSee($statement->number);
    }

    public function test_merchant_dashboard_shows_upcoming_fees_only_when_billing_is_on(): void
    {
        $billed = $this->merchant();
        $this->completedPayment($billed, 4000, '2026-11-01 00:30:00'); // 04:30 Dubai, 1 Nov
        $notBilled = $this->merchant(['billing_enabled' => false]);

        $this->actingAs($billed, 'merchants')->get(route('merchant.dashboard'))
            ->assertOk()->assertSee('Upcoming Edfundo Fees')->assertSee('AED 60.00');

        $this->actingAs($notBilled, 'merchants')->get(route('merchant.dashboard'))
            ->assertOk()->assertDontSee('Upcoming Edfundo Fees');
    }
}
