<?php

namespace Tests\Unit;

use App\Services\TelrService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TelrServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telr.store_id' => '29840',
            'telr.auth_key' => 'remote-secret-key',
            'telr.order_auth_key' => 'order-secret-key',
            'telr.test_mode' => true,
            'telr.order_url' => 'https://telr.test/order.json',
            'telr.remote_url' => 'https://telr.test/remote.xml',
        ]);
    }

    private function xmlResponse(string $status, string $message = 'Authorised', string $tranref = '030130193252', string $code = '9792471'): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><remote><auth><status>' . $status . '</status><code>' . $code
            . '</code><message>' . $message . '</message><tranref>' . $tranref . '</tranref><cvv>X</cvv><avs>X</avs>'
            . '<cartid>EP-1</cartid><trace>4000/1/abc</trace></auth></remote>';
    }

    public function test_continuous_charge_is_authorised_and_sends_the_right_request(): void
    {
        Http::fake(['telr.test/remote.xml' => Http::response($this->xmlResponse('A'), 200)]);

        $result = app(TelrService::class)->chargeContinuous('030130190634', 2.5, 'EP-202610-0007-A1', 'Edfundo fees October 2026');

        $this->assertSame('authorised', $result['outcome']);
        $this->assertSame('030130193252', $result['tran_ref']);

        Http::assertSent(function ($request) {
            $body = $request->body();
            return $request->url() === 'https://telr.test/remote.xml'
                && str_contains($body, '<store>29840</store>')
                && str_contains($body, '<key>remote-secret-key</key>')
                && str_contains($body, '<type>sale</type>')
                && str_contains($body, '<class>cont</class>')
                && str_contains($body, '<test>1</test>')
                && str_contains($body, '<amount>2.50</amount>')
                && str_contains($body, '<ref>030130190634</ref>')
                && str_contains($body, '<cartid>EP-202610-0007-A1</cartid>');
        });
    }

    public function test_declined_charge(): void
    {
        Http::fake(['telr.test/remote.xml' => Http::response($this->xmlResponse('D', 'Insufficient Funds', '', '41'), 200)]);

        $result = app(TelrService::class)->chargeContinuous('030130190634', 10, 'EP-1', 'x');

        $this->assertSame('declined', $result['outcome']);
        $this->assertSame('Insufficient Funds', $result['message']);
        $this->assertSame('41', $result['code']);
    }

    public function test_held_for_review_is_treated_as_unknown_so_it_is_never_auto_retried(): void
    {
        Http::fake(['telr.test/remote.xml' => Http::response($this->xmlResponse('H', 'On hold'), 200)]);

        $this->assertSame('unknown', app(TelrService::class)->chargeContinuous('1', 10, 'EP-1', 'x')['outcome']);
    }

    public function test_timeouts_and_http_errors_are_unknown_not_declined(): void
    {
        Http::fake(['telr.test/remote.xml' => Http::response('Gateway Timeout', 504)]);
        $this->assertSame('unknown', app(TelrService::class)->chargeContinuous('1', 10, 'EP-1', 'x')['outcome']);

        Http::fake(['telr.test/remote.xml' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out')]);
        $this->assertSame('unknown', app(TelrService::class)->chargeContinuous('1', 10, 'EP-1', 'x')['outcome']);
    }

    public function test_garbled_body_is_unknown(): void
    {
        Http::fake(['telr.test/remote.xml' => Http::response('<<not xml', 200)]);

        $this->assertSame('unknown', app(TelrService::class)->chargeContinuous('1', 10, 'EP-1', 'x')['outcome']);
    }

    public function test_request_level_error_response_means_nothing_was_charged(): void
    {
        $body = '<?xml version="1.0"?><Response><Method>sale</Method><Error>Invalid request</Error><Details>Invalid key</Details></Response>';
        Http::fake(['telr.test/remote.xml' => Http::response($body, 200)]);

        $result = app(TelrService::class)->chargeContinuous('1', 10, 'EP-1', 'x');

        $this->assertSame('error', $result['outcome']);
        $this->assertStringContainsString('Invalid key', $result['message']);
    }

    public function test_refund_is_an_ecom_refund_and_accepted(): void
    {
        Http::fake(['telr.test/remote.xml' => Http::response($this->xmlResponse('A', 'Accepted', '030130193357'), 200)]);

        $result = app(TelrService::class)->refund('030130190634', 1.0, 'EPREF-1');

        $this->assertSame('authorised', $result['outcome']);
        Http::assertSent(fn ($r) => str_contains($r->body(), '<type>refund</type>') && str_contains($r->body(), '<class>ecom</class>') && str_contains($r->body(), '<amount>1.00</amount>'));
    }

    public function test_xml_special_characters_are_escaped(): void
    {
        Http::fake(['telr.test/remote.xml' => Http::response($this->xmlResponse('A'), 200)]);

        app(TelrService::class)->chargeContinuous('1', 10, 'EP-1', 'Fees <&> "Oct"');

        Http::assertSent(fn ($r) => str_contains($r->body(), 'Fees &lt;&amp;&gt; &quot;Oct&quot;'));
    }

    public function test_card_setup_order_uses_the_hosted_page_key_and_returns_the_url(): void
    {
        Http::fake(['telr.test/order.json' => Http::response([
            'method' => 'create',
            'order' => ['ref' => 'ABC123', 'url' => 'https://secure.telr.com/gateway/process.html?o=ABC123'],
        ], 200)]);

        $result = app(TelrService::class)->createCardSetupOrder('EPCARD-1', 1.0, [
            'authorised' => 'https://x/a', 'declined' => 'https://x/d', 'cancelled' => 'https://x/c',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('ABC123', $result['order_ref']);

        Http::assertSent(fn ($r) => $r['authkey'] === 'order-secret-key' && $r['order']['amount'] === '1.00' && $r['order']['test'] === '1' && $r['method'] === 'create');
    }

    public function test_card_setup_order_failure_does_not_leak_keys_into_logs(): void
    {
        Http::fake(['telr.test/order.json' => Http::response(['error' => ['message' => 'E01:Invalid request', 'note' => 'Request authentication failed']], 200)]);

        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
            return !str_contains(json_encode($context), 'secret-key');
        });

        $result = app(TelrService::class)->createCardSetupOrder('EPCARD-1', 1.0, ['authorised' => 'a', 'declined' => 'd', 'cancelled' => 'c']);

        $this->assertFalse($result['success']);
    }

    public function test_check_order_extracts_reference_and_card_when_paid(): void
    {
        Http::fake(['telr.test/order.json' => Http::response([
            'method' => 'check',
            'order' => [
                'ref' => 'ABC', 'status' => ['code' => 3, 'text' => 'Paid'],
                'transaction' => ['ref' => '030130190634', 'type' => 'sale', 'class' => 'ECom', 'status' => 'A', 'message' => 'Authorised'],
                'card' => ['type' => 'Visa Credit', 'last4' => '1111', 'expiry' => ['month' => 12, 'year' => 2030]],
            ],
        ], 200)]);

        $result = app(TelrService::class)->checkOrder('ABC');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['paid']);
        $this->assertSame('030130190634', $result['tran_ref']);
        $this->assertSame('1111', $result['card']['last4']);
        $this->assertSame(2030, $result['card']['expiry_year']);
    }

    public function test_check_order_is_not_paid_when_cancelled_or_declined(): void
    {
        Http::fake(['telr.test/order.json' => Http::response(['order' => ['status' => ['code' => -3, 'text' => 'Cancelled']]], 200)]);

        $result = app(TelrService::class)->checkOrder('ABC');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['paid']);
    }

    public function test_unconfigured_service_never_calls_telr(): void
    {
        config(['telr.auth_key' => '']);
        Http::fake();

        $this->assertSame('error', app(TelrService::class)->chargeContinuous('1', 10, 'EP-1', 'x')['outcome']);
        Http::assertNothingSent();
    }
}
