<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_entities', function (Blueprint $table) {
            // Mirrors the merchant-level fallback_bank_name/fallback_account_name/iban
            // fields — so the "bank isn't listed" fallback shown to a payer
            // reflects the actual entity their invoice/product belongs to,
            // not always the merchant's own default bank details.
            $table->string('iban')->nullable()->after('lean_destination_id');
            $table->string('fallback_bank_name')->nullable()->after('iban');
            $table->string('fallback_account_name')->nullable()->after('fallback_bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_entities', function (Blueprint $table) {
            $table->dropColumn(['iban', 'fallback_bank_name', 'fallback_account_name']);
        });
    }
};
