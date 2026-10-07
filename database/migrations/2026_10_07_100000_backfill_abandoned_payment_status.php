<?php

use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Before the Abandoned status existed, ExpireStaleInvoices marked stuck
     * Initiated payments as Failed. Those are distinguishable from real
     * failures: a genuine Lean failure always carries a latest_webhook in
     * lean_metadata, so Failed rows with no webhook (and no SDK failure
     * event) are really abandoned checkouts.
     */
    public function up(): void
    {
        AppUserPayment::where('status', PaymentStatus::Failed->value)
            ->whereNotNull('lean_payment_intent_id')
            ->whereNull('flow_failure_at')
            ->chunkById(200, function ($payments) {
                foreach ($payments as $payment) {
                    if (empty($payment->lean_metadata['latest_webhook'] ?? null)) {
                        $payment->status = PaymentStatus::Abandoned;
                        $payment->saveQuietly();
                    }
                }
            });
    }

    public function down(): void
    {
        AppUserPayment::where('status', PaymentStatus::Abandoned->value)
            ->update(['status' => PaymentStatus::Failed->value]);
    }
};
