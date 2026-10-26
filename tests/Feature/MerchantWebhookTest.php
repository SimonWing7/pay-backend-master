<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Models\Consumer;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * What a merchant's own webhook endpoint receives when Lean reports an outcome.
 */
class MerchantWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const LEAN_SECRET = 'lean-test-secret';
    private const MERCHANT_SECRET = 'merchant-webhook-secret';

    private Merchant $merchant;
    private Invoice $invoice;
    private AppUserPayment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        config(['lean.webhook_secret' => self::LEAN_SECRET]);
        Http::fake(['merchant.test/*' => Http::response('OK', 200)]);

        $this->merchant = Merchant::create([
            'name' => 'Shop', 'email' => 'shop@example.test', 'password' => 'password123',
            'is_active' => true, 'must_change_password' => false,
            'webhook_url' => 'https://merchant.test/hooks/edfundo', 'webhook_secret' => self::MERCHANT_SECRET,
        ]);
        $consumer = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'Ahmed', 'email' => 'ahmed@example.test', 'mobile_number' => '+971501234567']);
        $this->invoice = Invoice::create([
            'merchant_id' => $this->merchant->id, 'consumer_id' => $consumer->id, 'total_fee' => 250,
            'status' => InvoiceStatus::Draft, 'reference' => 'ORDER-1042', 'link_type' => 'personal',
        ]);
        InvoiceDetail::create(['invoice_id' => $this->invoice->id, 'title' => 'Annual Membership', 'fee' => 250]);
        $this->payment = AppUserPayment::create([
            'invoice_id' => $this->invoice->id, 'payment_channel' => 'web', 'status' => PaymentStatus::Initiated,
            'token' => 'tok-1', 'lean_payment_intent_id' => 'intent-1',
        ]);
    }

    private function leanEvent(string $status): void
    {
        $body = json_encode(['type' => 'payment.updated', 'payload' => ['intent_id' => 'intent-1', 'status' => $status]]);
        $signature = hash_hmac('sha512', $body, self::LEAN_SECRET);

        $this->call('POST', route('webhook.lean'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_LEAN_SIGNATURE' => $signature,
        ], $body)->assertOk();
    }

    private function sentToMerchant(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'merchant.test'))
            ->map(fn ($pair) => $pair[0])->all();
    }

    private function decoded(Request $request): array
    {
        return json_decode($request->body(), true);
    }

    public function test_a_confirmed_payment_sends_payment_paid_with_id_and_reference_first(): void
    {
        $this->leanEvent('ACCEPTED_BY_BANK');

        $sent = $this->sentToMerchant();
        $this->assertCount(1, $sent);

        $body = $this->decoded($sent[0]);
        $this->assertSame('payment.paid', $body['event']);
        // The two fields existing integrations (e.g. the Magento module) rely on
        $this->assertSame($this->invoice->uuid, $body['data']['id']);
        $this->assertSame('ORDER-1042', $body['data']['reference']);
        // The richer detail
        $this->assertSame('paid', $body['data']['status']);
        $this->assertEquals(250.0, $body['data']['amount']);
        $this->assertSame('AED', $body['data']['currency']);
        $this->assertSame('Annual Membership', $body['data']['description']);
        $this->assertSame('ahmed@example.test', $body['data']['customer']['email']);
        $this->assertSame($this->invoice->uuid, $body['data']['payment_link_id']);
        $this->assertNotNull($body['data']['paid_at']);
        $this->assertArrayNotHasKey('failure_reason', $body['data']);
    }

    public function test_the_webhook_is_signed_with_the_merchants_secret(): void
    {
        $this->leanEvent('ACCEPTED_BY_BANK');

        $request = $this->sentToMerchant()[0];
        $expected = 'sha256=' . hash_hmac('sha256', $request->body(), self::MERCHANT_SECRET);
        $this->assertSame($expected, $request->header('X-Edfundo-Signature')[0]);
    }

    public function test_a_failed_payment_sends_payment_failed(): void
    {
        $this->leanEvent('FAILED');

        $sent = $this->sentToMerchant();
        $this->assertCount(1, $sent);

        $body = $this->decoded($sent[0]);
        $this->assertSame('payment.failed', $body['event']);
        $this->assertSame($this->invoice->uuid, $body['data']['id']);
        $this->assertSame('ORDER-1042', $body['data']['reference']);
        $this->assertSame('failed', $body['data']['status']);
        $this->assertSame('failed', $body['data']['failure_reason']);
        $this->assertNull($body['data']['paid_at']);
        $this->assertEquals(250.0, $body['data']['amount']);
    }

    public function test_rejected_and_cancelled_are_reported_with_their_reason(): void
    {
        foreach (['REJECTED' => 'rejected', 'CANCELLED' => 'cancelled'] as $iso => $reason) {
            // Reset in the database (the in-memory model still believes it is Initiated)
            AppUserPayment::whereKey($this->payment->id)->update(['status' => PaymentStatus::Initiated->value]);
            Http::fake(['merchant.test/*' => Http::response('OK', 200)]);

            $this->leanEvent($iso);

            $sent = array_values(array_filter($this->sentToMerchant(), fn ($r) => str_contains($r->body(), '"payment.failed"')));
            $this->assertNotEmpty($sent, $iso);
            $this->assertSame($reason, $this->decoded(end($sent))['data']['failure_reason'], $iso);
        }
    }

    public function test_a_repeated_failure_notice_from_the_bank_sends_only_one_webhook(): void
    {
        $this->leanEvent('FAILED');
        $this->leanEvent('FAILED');
        $this->leanEvent('REJECTED');

        $this->assertCount(1, $this->sentToMerchant());
    }

    public function test_a_late_failure_never_overrides_or_announces_over_a_paid_payment(): void
    {
        $this->leanEvent('ACCEPTED_BY_BANK');
        $this->leanEvent('FAILED');

        $events = array_map(fn ($r) => $this->decoded($r)['event'], $this->sentToMerchant());
        $this->assertSame(['payment.paid'], $events);
        $this->assertSame(PaymentStatus::Complete, $this->payment->fresh()->status);
    }

    public function test_intermediate_bank_updates_send_nothing(): void
    {
        $this->leanEvent('PENDING');
        $this->leanEvent('PENDING_WITH_BANK');
        $this->leanEvent('ACCEPTED_SETTLEMENT_IN_PROCESS');

        $this->assertCount(0, $this->sentToMerchant());
    }

    public function test_no_webhook_is_attempted_without_a_configured_url(): void
    {
        $this->merchant->update(['webhook_url' => null, 'webhook_secret' => null]);

        $this->leanEvent('FAILED');

        $this->assertCount(0, $this->sentToMerchant());
        $this->assertSame(PaymentStatus::Failed, $this->payment->fresh()->status);
    }

    public function test_a_broken_merchant_endpoint_never_breaks_the_payment_update(): void
    {
        Http::fake(['merchant.test/*' => Http::response('boom', 500)]);

        $this->leanEvent('FAILED');

        $this->assertSame(PaymentStatus::Failed, $this->payment->fresh()->status);
    }

    public function test_an_open_link_stays_payable_but_the_failed_attempt_is_still_announced(): void
    {
        $this->invoice->update(['link_type' => 'open']);

        $this->leanEvent('FAILED');

        $this->assertSame(InvoiceStatus::Draft, $this->invoice->fresh()->status);
        $this->assertCount(1, $this->sentToMerchant());
    }

    public function test_the_public_developer_docs_describe_the_events_that_are_really_sent(): void
    {
        $html = $this->get('/developers')->assertOk()->getContent();

        $this->assertStringContainsString('payment.paid', $html);
        $this->assertStringContainsString('payment.failed', $html);
        $this->assertStringContainsString('failure_reason', $html);
        $this->assertStringContainsString('3.124.43.40', $html);
        // The older mobile-app event name must not be documented for merchants.
        $this->assertStringNotContainsString('payment.completed', $html);
    }
}
