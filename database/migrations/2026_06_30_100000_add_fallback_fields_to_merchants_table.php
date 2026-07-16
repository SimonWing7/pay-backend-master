<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Fallback payment method shown when customer's bank is not available
            // Values: null / 'payment_gateway' / 'bank_transfer'
            $table->string('fallback_type', 30)->nullable()->after('webhook_secret');

            // Payment gateway: a URL the customer is redirected to (e.g. Stripe, PayTabs)
            $table->string('fallback_payment_url', 2048)->nullable()->after('fallback_type');

            // Bank transfer: details shown to the customer so they can pay manually
            $table->string('fallback_bank_name', 255)->nullable()->after('fallback_payment_url');
            $table->string('fallback_account_name', 255)->nullable()->after('fallback_bank_name');
            $table->text('fallback_reference_note')->nullable()->after('fallback_account_name');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'fallback_type',
                'fallback_payment_url',
                'fallback_bank_name',
                'fallback_account_name',
                'fallback_reference_note',
            ]);
        });
    }
};
