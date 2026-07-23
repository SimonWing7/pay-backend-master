<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('reference');
        });

        Schema::table('app_user_payments', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('payment_channel');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('customer_mobile')->nullable()->after('customer_email');
            $table->json('custom_field_values')->nullable()->after('customer_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::table('app_user_payments', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_email', 'customer_mobile', 'custom_field_values']);
        });
    }
};
