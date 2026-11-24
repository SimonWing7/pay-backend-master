<?php

namespace App\Console\Commands;

use App\Services\WebhookService;
use Illuminate\Console\Command;

class RetryWebhooks extends Command
{
    protected $signature = 'webhooks:retry {--limit=100 : Most deliveries to retry this run}';

    protected $description = 'Retry merchant webhook notifications that failed to deliver';

    public function handle(WebhookService $webhooks): int
    {
        $r = $webhooks->retryDue((int) $this->option('limit'));

        if ($r['attempted'] || $r['superseded']) {
            $this->info("Retried {$r['attempted']}, delivered {$r['delivered']}, skipped {$r['superseded']} no longer needed.");
        }

        return self::SUCCESS;
    }
}
