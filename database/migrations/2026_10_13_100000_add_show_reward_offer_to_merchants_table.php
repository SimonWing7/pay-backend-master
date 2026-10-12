<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Edfundo's own payment links go to existing customers renewing a
            // subscription, who shouldn't be shown the new-customer AED 50 offer.
            $table->boolean('show_reward_offer')->default(true)->after('allow_payment_reconciliation');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('show_reward_offer');
        });
    }
};
