<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConfirmPayment extends Command
{
    protected $signature = 'payments:confirm
        {payment_id : The Edfundo Pay payment ID (the # shown in the admin dashboard)}
        {--lean-payment-id= : The Payment ID shown as Processed in Lean\'s own dashboard — must match the one we stored}
        {--skip-receipt : Don\'t email the customer a receipt}';

    protected $description = 'Manually confirm a Lean payment as paid when Lean shows it as Processed but its final webhook never reached us';

    public function handle(PaymentService $paymentService): int
    {
        $payment = AppUserPayment::with('invoice.merchant')->find($this->argument('payment_id'));

        if (!$payment) {
            $this->error('Payment not found.');
            return self::FAILURE;
        }

        if ($payment->status === PaymentStatus::Complete) {
            $this->error('This payment is already Complete — nothing to do.');
            return self::FAILURE;
        }

        $storedLeanPaymentId = $payment->lean_metadata['latest_webhook']['payload']['id'] ?? null;
        $givenLeanPaymentId = (string) $this->option('lean-payment-id');

        if (!$storedLeanPaymentId) {
            $this->error('No Lean payment ID is stored on this payment (no payment.created webhook was ever received), so it can\'t be verified against Lean. Refusing.');
            return self::FAILURE;
        }

        if ($givenLeanPaymentId !== $storedLeanPaymentId) {
            $this->error('--lean-payment-id is missing or doesn\'t match the Lean Payment ID stored on this payment (' . $storedLeanPaymentId . '). Check it against the payment in Lean\'s dashboard. Refusing.');
            return self::FAILURE;
        }

        $this->table(['Field', 'Value'], [
            ['Payment', '#' . $payment->id . ' (currently ' . $payment->status->label() . ')'],
            ['Invoice', ($payment->invoice->reference ?? '—') . ' (currently ' . ($payment->invoice->status->label() ?? '—') . ')'],
            ['Merchant', $payment->invoice->merchant->name ?? '—'],
            ['Amount', 'AED ' . number_format($payment->invoice->total_fee ?? 0, 2)],
            ['Customer', ($payment->customer_name ?? '—') . ' <' . ($payment->customer_email ?? '—') . '>'],
            ['Lean Payment ID', $storedLeanPaymentId],
            ['Receipt email', $this->option('skip-receipt') ? 'skipped' : ($payment->customer_email ?: 'no email on file')],
        ]);

        if (!$this->confirm('Mark this payment Complete and the invoice Paid, and notify the merchant\'s webhook?')) {
            $this->info('Cancelled — nothing changed.');
            return self::SUCCESS;
        }

        $meta = array_merge($payment->lean_metadata ?? [], [
            'manual_confirmation' => [
                'at'              => now()->toIso8601String(),
                'lean_payment_id' => $storedLeanPaymentId,
                'reason'          => 'Lean dashboard shows Processed; final webhook never received',
            ],
        ]);

        $paymentService->confirmLeanPayment($payment, $meta, !$this->option('skip-receipt'));

        Log::warning('payments:confirm: payment manually confirmed', [
            'payment_id'      => $payment->id,
            'lean_payment_id' => $storedLeanPaymentId,
        ]);

        $this->info("Payment #{$payment->id} confirmed.");
        return self::SUCCESS;
    }
}
