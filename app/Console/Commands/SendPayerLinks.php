<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Mail\InvoicePaymentLinkMail;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPayerLinks extends Command
{
    protected $signature = 'payers:send-links {--limit= : Most links to send this run (defaults to INVOICE_SEND_BATCH_SIZE)}';

    protected $description = 'Email queued payment links to payers in batches, so a 1,000-person send never ties up a web request';

    public function handle(): int
    {
        $limit = (int) ($this->option('limit') ?: config('invoices.send_batch_size', 100));

        // A run that died mid-send leaves invoices in "sending". Put them back
        // in the queue rather than losing them (worst case: one duplicate email).
        Invoice::where('link_email_status', 'sending')
            ->where('updated_at', '<', now()->subMinutes(10))
            ->update(['link_email_status' => 'queued']);

        // Paid (or removed) since being queued: nothing left to send.
        Invoice::where('link_email_status', 'queued')
            ->where('status', '!=', InvoiceStatus::Draft->value)
            ->update(['link_email_status' => 'not_sent']);

        $invoices = Invoice::with(['consumer', 'merchant', 'invoiceDetails.product', 'invoiceDetails.consumer'])
            ->where('link_email_status', 'queued')
            ->where('status', InvoiceStatus::Draft->value)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($invoices as $invoice) {
            // Claim it atomically so two overlapping runs can never both send it.
            $claimed = Invoice::whereKey($invoice->id)
                ->where('link_email_status', 'queued')
                ->update(['link_email_status' => 'sending', 'updated_at' => now()]);

            if (!$claimed) {
                continue;
            }

            $email = $invoice->consumer?->email;

            if (empty($email)) {
                $this->mark($invoice, 'failed', 'No email address on file.');
                $failed++;
                continue;
            }

            try {
                Mail::to($email)->send(new InvoicePaymentLinkMail($invoice));
                $this->mark($invoice, 'sent', null);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Payer payment-link email failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
                $this->mark($invoice, 'failed', mb_substr($e->getMessage(), 0, 250));
                $failed++;
            }
        }

        $remaining = Invoice::where('link_email_status', 'queued')->count();
        $this->info("Sent {$sent}, failed {$failed}, {$remaining} still queued.");

        return self::SUCCESS;
    }

    private function mark(Invoice $invoice, string $status, ?string $error): void
    {
        DB::table('invoices')->where('id', $invoice->id)->update([
            'link_email_status' => $status,
            'link_emailed_at'   => $status === 'sent' ? now() : null,
            'link_email_error'  => $error,
            'updated_at'        => now(),
        ]);
    }
}
