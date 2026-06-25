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
            $table->json('flow_success_data')->nullable()->after('nymcard_metadata');
            $table->json('flow_failure_data')->nullable()->after('flow_success_data');
            $table->json('flow_done_data')->nullable()->after('flow_failure_data');
            $table->timestamp('flow_success_at')->nullable()->after('flow_done_data');
            $table->timestamp('flow_failure_at')->nullable()->after('flow_success_at');
            $table->timestamp('flow_done_at')->nullable()->after('flow_failure_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_user_payments', function (Blueprint $table) {
            $table->dropColumn([
                'flow_success_data',
                'flow_failure_data',
                'flow_done_data',
                'flow_success_at',
                'flow_failure_at',
                'flow_done_at',
            ]);
        });
    }
};
