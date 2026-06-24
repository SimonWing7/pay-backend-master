<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds columns to support web-based Open Finance payments:
     * - payment_channel: 'mobile' (legacy app) or 'web' (new hosted page)
     * - nymcard_user_id: the NymCard userId returned on payment initiation
     */
    public function up(): void
    {
        Schema::table('app_user_payments', function (Blueprint $table) {
            $table->string('payment_channel')->default('mobile')->after('app_user_id');
            $table->string('nymcard_user_id')->nullable()->after('nymcard_resource_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_user_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_channel', 'nymcard_user_id']);
        });
    }
};
