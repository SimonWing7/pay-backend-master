<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Models\ProductReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendProductReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send scheduled payment reminders for products, skipping consumers who have already paid';

    public function handle(): int
    {
        $reminders = ProductReminder::whereNull('sent_at')
            ->whereDate('send_date', '<=', now())
            ->with('product')
            ->get();

        $sentTotal = 0;

        foreach ($reminders as $reminder) {
            $product = $reminder->product;
            if (!$product) {
                $reminder->update(['sent_at' => now()]);
                continue;
            }

            $invoices = Invoice::whereHas('invoiceDetails', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
                ->where('status', '!=', InvoiceStatus::Paid)
                ->whereNotNull('consumer_id')
                ->with(['consumer', 'merchant', 'invoiceDetails'])
                ->get();

            foreach ($invoices as $invoice) {
                if (!$invoice->consumer?->email) {
                    continue;
                }

                try {
                    Mail::to($invoice->consumer->email)->send(new InvoiceReminderMail($invoice, $reminder->message));
                    $sentTotal++;
                } catch (\Throwable $e) {
                    Log::error('Product reminder email failed', [
                        'product_reminder_id' => $reminder->id,
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $reminder->update(['sent_at' => now()]);
        }

        $this->info("Sent {$sentTotal} reminder email(s) across " . $reminders->count() . ' schedule(s).');
        return self::SUCCESS;
    }
}
