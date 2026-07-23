<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_user_payments', function (Blueprint $table) {
            // Lean payment intent ID (replaces nymcard_resource_id for new payments)
            $table->string('lean_payment_intent_id')->nullable()->after('nymcard_metadata');

            // Full Lean webhook/response metadata
            $table->json('lean_metadata')->nullable()->after('lean_payment_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_user_payments', function (Blueprint $table) {
            $table->dropColumn(['lean_payment_intent_id', 'lean_metadata']);
        });
    }
};
