<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * TEMPORARY — Phase 0 of monthly merchant billing. Prints Telr's RAW
 * responses so we can see exactly where the reusable transaction reference
 * comes from before building the real TelrService. Test mode only. Delete
 * once the real integration exists.
 *
 *   php artisan telr:spike create
 *   (open the printed URL, pay with a Telr TEST card, copy the address bar
 *    URL you land on afterwards)
 *   php artisan telr:spike check <order_ref>
 *   php artisan telr:spike charge <tran_ref> --amount=1.00
 *   php artisan telr:spike refund <tran_ref> --amount=1.00
 */
class TelrSpike extends Command
{
    protected $signature = 'telr:spike {action : create|check|charge|refund} {ref? : order ref (check) or transaction ref (charge/refund)} {--amount=1.00}';

    protected $description = 'TEMPORARY: probe Telr test-mode responses for the merchant billing build';

    public function handle(): int
    {
        if (!config('telr.store_id') || !config('telr.auth_key')) {
            $this->error('Set TELR_STORE_ID and TELR_AUTH_KEY in .env first (then run: php artisan config:clear).');
            return self::FAILURE;
        }

        if (!config('telr.test_mode')) {
            $this->error('Refusing to run: TELR_TEST_MODE is not true. This spike is for test mode only.');
            return self::FAILURE;
        }

        $action = $this->argument('action');
        $ref = $this->argument('ref');

        return match ($action) {
            'create' => $this->create(),
            'check'  => $ref ? $this->check($ref) : $this->missingRef(),
            'charge' => $ref ? $this->remote('sale', 'cont', $ref) : $this->missingRef(),
            'refund' => $ref ? $this->remote('refund', 'ecom', $ref) : $this->missingRef(),
            default  => $this->failWith('Unknown action. Use create, check, charge or refund.'),
        };
    }

    private function create(): int
    {
        $cartId = 'SPIKE-' . now()->format('YmdHis');

        $response = Http::timeout(config('telr.timeout'))->asJson()->acceptJson()->post(config('telr.order_url'), [
            'method'  => 'create',
            'store'   => (int) config('telr.store_id'),
            'authkey' => config('telr.order_auth_key'),
            'framed'  => 0,
            'order'   => [
                'cartid'      => $cartId,
                'test'        => '1',
                'amount'      => number_format((float) $this->option('amount'), 2, '.', ''),
                'currency'    => 'AED',
                'description' => 'Edfundo billing card test',
            ],
            // These pages don't exist — after paying you'll land on a 404.
            // That's fine: copy the URL from the address bar, it may carry
            // transaction details as query parameters.
            'return' => [
                'authorised' => url('/telr-spike/authorised'),
                'declined'   => url('/telr-spike/declined'),
                'cancelled'  => url('/telr-spike/cancelled'),
            ],
        ]);

        $this->line('HTTP ' . $response->status() . '  cartid ' . $cartId);
        $this->line($this->pretty($response->body()));

        if ($url = $response->json('order.url') ?? $response->json('url')) {
            $this->newLine();
            $this->info('Open this URL, pay with a Telr TEST card, then run: php artisan telr:spike check <order.ref>');
            $this->line($url);
        }

        return self::SUCCESS;
    }

    private function check(string $orderRef): int
    {
        $response = Http::timeout(config('telr.timeout'))->asJson()->acceptJson()->post(config('telr.order_url'), [
            'method'  => 'check',
            'store'   => (int) config('telr.store_id'),
            'authkey' => config('telr.order_auth_key'),
            'order'   => ['ref' => $orderRef],
        ]);

        $this->line('HTTP ' . $response->status());
        $this->line($this->pretty($response->body()));

        return self::SUCCESS;
    }

    private function remote(string $type, string $class, string $tranRef): int
    {
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_XML1);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<remote>'
            . '<store>' . $e(config('telr.store_id')) . '</store>'
            . '<key>' . $e(config('telr.auth_key')) . '</key>'
            . '<tran>'
            . '<type>' . $type . '</type>'
            . '<class>' . $class . '</class>'
            . '<cartid>' . $e('SPIKE-' . strtoupper($type) . '-' . now()->format('YmdHis')) . '</cartid>'
            . '<description>' . $e('Edfundo billing test ' . $type) . '</description>'
            . '<test>1</test>'
            . '<currency>AED</currency>'
            . '<amount>' . number_format((float) $this->option('amount'), 2, '.', '') . '</amount>'
            . '<ref>' . $e($tranRef) . '</ref>'
            . '</tran>'
            . '</remote>';

        $response = Http::timeout(config('telr.timeout'))
            ->withBody($xml, 'application/xml')
            ->post(config('telr.remote_url'));

        $this->line("{$type}/{$class}  HTTP " . $response->status());
        $this->line($response->body());

        return self::SUCCESS;
    }

    private function missingRef(): int
    {
        $this->error('This action needs a reference argument.');
        return self::FAILURE;
    }

    private function failWith(string $message): int
    {
        $this->error($message);
        return self::FAILURE;
    }

    private function pretty(string $body): string
    {
        $decoded = json_decode($body, true);

        return $decoded === null ? $body : json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
