<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Models\Consumer;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Merchant;
use App\Models\WebhookDelivery;
use App\Services\PaymentService;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Failed merchant webhooks are recorded, retried, and can be replayed.
 */
class WebhookRetryTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Invoice $invoice;
    private AppUserPayment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'Shop', 'email' => 'shop@example.test', 'password' => 'password123',
            'is_active' => true, 'must_change_password' => false,
            'webhook_url' => 'https://merchant.test/hooks', 'webhook_secret' => 'secret',
        ]);
        $consumer = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'Ahmed', 'email' => 'a@example.test', 'mobile_number' => '+971501234567']);
        $this->invoice = Invoice::create([
            'merchant_id' => $this->merchant->id, 'consumer_id' => $consumer->id, 'total_fee' => 590,
            'status' => InvoiceStatus::Paid, 'reference' => '2000055814', 'link_type' => 'personal',
        ]);
        InvoiceDetail::create(['invoice_id' => $this->invoice->id, 'title' => 'Order', 'fee' => 590]);
        $this->payment = AppUserPayment::create([
            'invoice_id' => $this->invoice->id, 'payment_channel' => 'web', 'status' => PaymentStatus::Complete, 'token' => 'tok-1',
        ]);
    }

    private function send(string $event = 'payment.paid'): WebhookDelivery
    {
        return app(WebhookService::class)->dispatch($this->merchant, $event, ['id' => 'u', 'reference' => '2000055814']);
    }

    private function hits(): int
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'merchant.test'))->count();
    }

    public function test_a_delivered_webhook_is_recorded_and_not_retried(): void
    {
        Http::fake(['merchant.test/*' => Http::response('OK', 200)]);

        $d = $this->send();

        $this->assertSame('delivered', $d->status);
        $this->assertSame(1, $d->attempts);
        $this->assertSame(['attempted' => 0, 'delivered' => 0, 'superseded' => 0], app(WebhookService::class)->retryDue());
    }

    public function test_a_blocked_webhook_is_queued_for_retry_then_delivered(): void
    {
        Http::fake(['merchant.test/*' => Http::sequence()
            ->push('<html>Just a moment...</html>', 403)
            ->push('OK', 200)]);

        $d = $this->send();

        $this->assertSame('pending', $d->status);
        $this->assertSame(403, $d->last_http_status);
        $this->assertNotNull($d->next_attempt_at);

        // Not due yet.
        $this->assertSame(0, app(WebhookService::class)->retryDue()['attempted']);

        $this->travel(2)->minutes();
        $result = app(WebhookService::class)->retryDue();

        $this->assertSame(1, $result['delivered']);
        $this->assertSame('delivered', $d->fresh()->status);
        $this->assertSame(2, $d->fresh()->attempts);
        $this->assertSame(2, $this->hits());
    }

    public function test_it_gives_up_after_the_last_retry(): void
    {
        Http::fake(['merchant.test/*' => Http::response('no', 500)]);

        $d = $this->send();
        foreach (WebhookService::RETRY_DELAYS as $delay) {
            $this->travel($delay + 1)->seconds();
            app(WebhookService::class)->retryDue();
        }

        $d->refresh();
        $this->assertSame('failed', $d->status);
        $this->assertSame(count(WebhookService::RETRY_DELAYS) + 1, $d->attempts);
        $this->assertNull($d->next_attempt_at);
    }

    public function test_a_queued_failed_notice_is_dropped_once_the_order_was_paid(): void
    {
        Http::fake(['merchant.test/*' => Http::response('no', 500)]);

        $d = $this->send('payment.failed'); // invoice is already Paid
        $this->travel(2)->minutes();

        $result = app(WebhookService::class)->retryDue();

        $this->assertSame(1, $result['superseded']);
        $this->assertSame('superseded', $d->fresh()->status);
        $this->assertSame(1, $this->hits()); // only the first attempt
    }

    public function test_every_attempt_carries_a_valid_signature_and_delivery_id(): void
    {
        Http::fake(['merchant.test/*' => Http::response('OK', 200)]);

        $d = $this->send();

        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Edfundo-Delivery', (string) $d->id)
            && $r->header('X-Edfundo-Signature')[0] === 'sha256=' . hash_hmac('sha256', $r->body(), 'secret'));
    }

    public function test_resend_command_replays_payment_paid_for_missed_payments(): void
    {
        Http::fake(['merchant.test/*' => Http::response('OK', 200)]);

        $this->artisan('webhooks:resend-paid', ['--merchant' => $this->merchant->id, '--force' => true])
            ->assertSuccessful();

        Http::assertSent(function (Request $r) {
            $body = json_decode($r->body(), true);
            return $body['event'] === 'payment.paid'
                && $body['data']['reference'] === '2000055814'
                && $body['data']['status'] === 'paid';
        });
    }

    public function test_resend_dry_run_sends_nothing(): void
    {
        Http::fake();

        $this->artisan('webhooks:resend-paid', ['--merchant' => $this->merchant->id, '--dry-run' => true])
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, WebhookDelivery::count());
    }

    public function test_resend_command_can_target_one_reference(): void
    {
        Http::fake(['merchant.test/*' => Http::response('OK', 200)]);

        $this->artisan('webhooks:resend-paid', ['--merchant' => $this->merchant->id, '--reference' => 'NOPE', '--force' => true])
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_admin_can_resend_from_the_payment_page(): void
    {
        Http::fake(['merchant.test/*' => Http::response('OK', 200)]);
        $admin = \App\Models\Admin::create(['name' => 'A', 'email' => 'admin@example.test', 'password' => 'password123']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.payments.resend_webhook', $this->payment->id))
            ->assertRedirect(route('admin.payments.show', $this->payment->id))
            ->assertSessionHas('success');

        $this->assertSame(1, WebhookDelivery::where('event', 'payment.paid')->where('status', 'delivered')->count());

        $this->actingAs($admin, 'admin')
            ->get(route('admin.payments.show', $this->payment->id))
            ->assertOk()->assertSee('Resend payment.paid')->assertSee('Delivered');
    }
}
