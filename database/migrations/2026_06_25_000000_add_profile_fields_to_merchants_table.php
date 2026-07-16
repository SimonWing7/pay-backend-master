<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('support_email')->nullable()->after('merchant_trading_name');
            $table->string('support_phone')->nullable()->after('support_email');
            $table->string('website')->nullable()->after('support_phone');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['support_email', 'support_phone', 'website']);
        });
    }
};
