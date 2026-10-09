<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Opt-in: lets the merchant confirm/reject their own payments
            // that Lean left at PENDING_WITH_BANK, instead of Edfundo doing it.
            $table->boolean('allow_payment_reconciliation')->default(false)->after('notification_email');
        });

        Schema::table('app_user_payments', function (Blueprint $table) {
            // So a stuck payment is only emailed to the merchant once.
            $table->timestamp('review_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('allow_payment_reconciliation');
        });

        Schema::table('app_user_payments', function (Blueprint $table) {
            $table->dropColumn('review_notified_at');
        });
    }
};
