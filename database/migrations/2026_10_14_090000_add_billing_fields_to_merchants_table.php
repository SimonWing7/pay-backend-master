<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Edfundo's cut of this merchant's completed payments.
            $table->decimal('fee_percentage', 5, 2)->default(1.50)->after('show_reward_offer');
            // A merchant is only billed once an admin switches this on.
            $table->boolean('billing_enabled')->default(false)->after('fee_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['fee_percentage', 'billing_enabled']);
        });
    }
};
