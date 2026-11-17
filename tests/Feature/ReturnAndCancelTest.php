<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Merchant;
use App\Models\MerchantApiKey;
use App\Services\LeanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the customer sees after paying, and the "bank isn't listed" way back to the merchant.
 */
class ReturnAndCancelTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'Smart Runners', 'email' => 'sr@example.test', 'password' => 'password123',
            'is_active' => true, 'must_change_password' => false,
        ]);
        $this->apiKey = MerchantApiKey::generate($this->merchant->id, 'test')['plaintext'];

        // The return page asks Lean for an app token; the page itself doesn't need Lean.
        $this->mock(LeanService::class, function ($mock) {
            $mock->shouldReceive('getAppToken')->andReturn('token');
            $mock->shouldReceive('isSandbox')->andReturn(true);
            $mock->shouldReceive('getCustomerToken')->andReturn('');
        });
    }

    private function createLink(array $extra = [])
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->apiKey)->postJson('/api/v1/payment-links', array_merge([
            'amount' => 120, 'description' => 'Race entry', 'reference' => 'R-' . uniqid(),
            'return_url' => 'https://smartrunners.test/done', 'cancel_url' => 'https://smartrunners.test/cancel',
        ], $extra));
    }

    private function invoice(array $attrs = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'merchant_id' => $this->merchant->id, 'total_fee' => 120, 'status' => InvoiceStatus::Draft,
            'link_type' => 'personal',
            'return_url' => 'https://smartrunners.test/done', 'cancel_url' => 'https://smartrunners.test/cancel',
        ], $attrs));
        InvoiceDetail::create(['invoice_id' => $invoice->id, 'title' => 'Race entry', 'fee' => 120]);

        return $invoice;
    }

    private function returnPage(Invoice $invoice, string $status = 'success')
    {
        $payment = AppUserPayment::create([
            'invoice_id' => $invoice->id, 'payment_channel' => 'web', 'status' => PaymentStatus::Initiated,
            'token' => 't-' . uniqid(), 'lean_payment_intent_id' => 'intent-' . $invoice->id,
        ]);

        return $this->get(route('public.payment.return', ['intent_id' => $payment->lean_payment_intent_id, 'status' => $status]));
    }

    // -- after paying -------------------------------------------------------

    public function test_a_paid_customer_gets_a_clear_return_button_and_an_automatic_redirect(): void
    {
        $invoice = $this->invoice(['status' => InvoiceStatus::Paid]);

        $response = $this->returnPage($invoice)->assertOk();

        $response->assertSee('Return to Smart Runners', false);
        $response->assertSee('id="returnBtn"', false);
        $response->assertSee('Taking you back automatically in', false);
        $response->assertSee('Stay on this page', false);
        // 8 seconds, then straight to the merchant carrying the payment link id
        $response->assertSee('var secs  = 8;', false);
        $response->assertSee('href="' . e('https://smartrunners.test/done?status=paid&payment_link_id=' . $invoice->uuid) . '"', false);
    }

    public function test_a_payment_the_bank_has_not_confirmed_yet_still_gets_a_way_back_marked_pending(): void
    {
        // Lean's confirmation often arrives after the customer does.
        $invoice = $this->invoice(['status' => InvoiceStatus::Draft]);

        $response = $this->returnPage($invoice)->assertOk();

        $response->assertSee('id="returnBtn"', false);
        $response->assertSee('status=pending', false);
        $response->assertDontSee('status=paid', false);
    }

    public function test_no_return_button_or_redirect_without_a_return_url(): void
    {
        $invoice = $this->invoice(['status' => InvoiceStatus::Paid, 'return_url' => null]);

        $this->returnPage($invoice)->assertOk()
            ->assertDontSee('id="returnBtn"', false)
            ->assertDontSee('Taking you back automatically', false);
    }

    public function test_a_script_url_is_never_used_as_the_return_link(): void
    {
        $invoice = $this->invoice(['status' => InvoiceStatus::Paid, 'return_url' => 'javascript:alert(1)']);

        $this->returnPage($invoice)->assertOk()->assertDontSee('id="returnBtn"', false);
    }

    public function test_the_reward_offer_is_still_shown_alongside_the_countdown(): void
    {
        $invoice = $this->invoice(['status' => InvoiceStatus::Paid]);

        $this->returnPage($invoice)->assertOk()
            ->assertSee('Claim your AED 50 reward', false)
            ->assertSee('id="rewardLink"', false);
    }

    public function test_the_merchant_wide_setting_still_works_when_the_link_has_no_alternative(): void
    {
        $this->merchant->update(['fallback_type' => 'payment_gateway', 'fallback_payment_url' => 'https://merchant-wide.test/pay']);
        $invoice = $this->invoice(['cancel_url' => null]);

        $this->get(route('public.invoice.show', $invoice->uuid))->assertOk()
            ->assertSee('https://merchant-wide.test/pay', false)
            ->assertSee('Pay by card instead', false)
            ->assertDontSee('chooseAnotherMethod', false);
    }

    public function test_a_link_with_a_cancel_url_gets_a_back_to_choose_another_method_button(): void
    {
        $invoice = $this->invoice();

        $this->get(route('public.invoice.show', $invoice->uuid))->assertOk()
            ->assertSee('id="chooseAnotherMethod"', false)
            ->assertSee('to choose another payment method', false)
            ->assertSee('status=cancelled', false)
            ->assertSee('payment_link_id=' . $invoice->uuid, false);
    }

    public function test_no_back_button_without_a_cancel_url_and_none_for_script_urls(): void
    {
        $plain = $this->invoice(['cancel_url' => null]);
        $this->get(route('public.invoice.show', $plain->uuid))->assertOk()->assertDontSee('chooseAnotherMethod', false);

        $unsafe = $this->invoice(['cancel_url' => 'javascript:alert(1)']);
        $this->get(route('public.invoice.show', $unsafe->uuid))->assertOk()->assertDontSee('chooseAnotherMethod', false);
    }

    public function test_the_open_payment_link_and_product_pages_are_unchanged(): void
    {
        $product = \App\Models\Product::create(['merchant_id' => $this->merchant->id, 'name' => 'Trip', 'description' => 'x', 'fee' => 50, 'state' => 'active']);

        $this->get(route('public.product', $product->uuid))->assertOk()
            ->assertSee("My bank isn&#039;t listed yet", false)
            ->assertDontSee('chooseAnotherMethod', false);
    }
}
