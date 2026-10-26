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
 * Payments and links that never reach an outcome are closed after 24 hours.
 * The merchant's webhook hears about it as payment.failed so it can release the order.
 */
class ExpiryWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Consumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['merchant.test/*' => Http::response('OK', 200)]);

        $this->merchant = Merchant::create([
            'name' => 'Shop', 'email' => 'shop@example.test', 'password' => 'password123',
            'is_active' => true, 'must_change_password' => false,
            'webhook_url' => 'https://merchant.test/hooks', 'webhook_secret' => 'secret',
        ]);
        $this->consumer = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'Ahmed', 'email' => 'a@example.test']);
    }

    private function link(string $reference, string $type = 'personal', int $ageHours = 30, InvoiceStatus $status = InvoiceStatus::Draft): Invoice
    {
        $invoice = Invoice::create([
            'merchant_id' => $this->merchant->id, 'consumer_id' => $this->consumer->id, 'total_fee' => 100,
            'status' => $status, 'reference' => $reference, 'link_type' => $type,
        ]);
        InvoiceDetail::create(['invoice_id' => $invoice->id, 'title' => 'Order ' . $reference, 'fee' => 100]);
        $invoice->forceFill(['created_at' => now()->subHours($ageHours)])->saveQuietly();

        return $invoice;
    }

    private function attempt(Invoice $invoice, ?string $leanStatus = null, int $ageHours = 30): AppUserPayment
    {
        $payment = AppUserPayment::create([
            'invoice_id' => $invoice->id, 'payment_channel' => 'web', 'status' => PaymentStatus::Initiated,
            'token' => 'tok-' . uniqid(), 'lean_payment_intent_id' => 'intent-' . uniqid(),
            'lean_metadata' => $leanStatus ? ['latest_iso_status' => $leanStatus] : null,
        ]);
        $payment->forceFill(['created_at' => now()->subHours($ageHours)])->saveQuietly();

        return $payment;
    }

    private function events(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'merchant.test'))
            ->map(fn ($pair) => json_decode($pair[0]->body(), true))->values()->all();
    }

    private function expire(): void
    {
        $this->artisan('invoices:expire-stale')->assertSuccessful();
    }

    public function test_an_abandoned_payment_sends_payment_failed_with_reason_abandoned(): void
    {
        $invoice = $this->link('ORDER-1');
        $payment = $this->attempt($invoice);

        $this->expire();

        $this->assertSame(PaymentStatus::Abandoned, $payment->fresh()->status);

        $events = $this->events();
        $this->assertCount(1, $events);
        $this->assertSame('payment.failed', $events[0]['event']);
        $this->assertSame($invoice->uuid, $events[0]['data']['id']);
        $this->assertSame('ORDER-1', $events[0]['data']['reference']);
        $this->assertSame($payment->id, $events[0]['data']['payment_id']);
        $this->assertSame('failed', $events[0]['data']['status']);
        $this->assertSame('abandoned', $events[0]['data']['failure_reason']);
    }

    public function test_a_link_nobody_started_paying_sends_payment_failed_with_reason_expired(): void
    {
        $invoice = $this->link('ORDER-2');

        $this->expire();

        $this->assertSame(InvoiceStatus::Failed, $invoice->fresh()->status);

        $events = $this->events();
        $this->assertCount(1, $events);
        $this->assertSame('expired', $events[0]['data']['failure_reason']);
        $this->assertSame('ORDER-2', $events[0]['data']['reference']);
        $this->assertNull($events[0]['data']['payment_id']);
        $this->assertEquals(100.0, $events[0]['data']['amount']);
    }

    public function test_a_link_with_an_abandoned_attempt_gets_one_event_not_two(): void
    {
        $this->attempt($this->link('ORDER-3'));

        $this->expire();

        $reasons = array_map(fn ($e) => $e['data']['failure_reason'], $this->events());
        $this->assertSame(['abandoned'], $reasons);
    }

    public function test_running_the_job_again_does_not_repeat_the_events(): void
    {
        $this->attempt($this->link('ORDER-4'));
        $this->link('ORDER-5');

        $this->expire();
        $this->expire();

        $this->assertCount(2, $this->events());
    }

    public function test_recent_links_and_payments_are_left_alone(): void
    {
        $this->attempt($this->link('ORDER-6', 'personal', 2), null, 2);
        $this->link('ORDER-7', 'personal', 3);

        $this->expire();

        $this->assertCount(0, $this->events());
    }

    public function test_a_payment_pending_with_the_bank_is_never_expired_or_announced(): void
    {
        $invoice = $this->link('ORDER-8');
        $payment = $this->attempt($invoice, 'PENDING_WITH_BANK');

        $this->expire();

        $this->assertSame(PaymentStatus::Initiated, $payment->fresh()->status);
        $this->assertSame(InvoiceStatus::Draft, $invoice->fresh()->status);
        $this->assertCount(0, $this->events());
    }

    public function test_open_links_never_expire_but_an_abandoned_attempt_on_one_is_announced(): void
    {
        $open = $this->link('SHARED', 'open');
        $payment = $this->attempt($open);

        $this->expire();

        $this->assertSame(InvoiceStatus::Draft, $open->fresh()->status);
        $this->assertSame(PaymentStatus::Abandoned, $payment->fresh()->status);
        $this->assertSame(['abandoned'], array_map(fn ($e) => $e['data']['failure_reason'], $this->events()));
    }

    public function test_an_old_attempt_on_an_already_paid_link_is_not_announced_as_failed(): void
    {
        $paid = $this->link('ORDER-9', 'personal', 30, InvoiceStatus::Paid);
        $payment = $this->attempt($paid);

        $this->expire();

        // Still tidied up internally, but the merchant is not told a paid order failed.
        $this->assertSame(PaymentStatus::Abandoned, $payment->fresh()->status);
        $this->assertCount(0, $this->events());
    }

    public function test_a_merchant_without_a_webhook_is_simply_skipped(): void
    {
        $this->merchant->update(['webhook_url' => null, 'webhook_secret' => null]);
        $invoice = $this->link('ORDER-10');

        $this->expire();

        $this->assertSame(InvoiceStatus::Failed, $invoice->fresh()->status);
        $this->assertCount(0, $this->events());
    }

    public function test_a_broken_merchant_endpoint_does_not_stop_the_cleanup(): void
    {
        Http::fake(['merchant.test/*' => Http::response('boom', 500)]);
        $first = $this->link('ORDER-11');
        $second = $this->link('ORDER-12');

        $this->expire();

        $this->assertSame(InvoiceStatus::Failed, $first->fresh()->status);
        $this->assertSame(InvoiceStatus::Failed, $second->fresh()->status);
    }
}
