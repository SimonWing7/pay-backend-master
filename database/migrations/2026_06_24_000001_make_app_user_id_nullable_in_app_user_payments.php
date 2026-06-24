<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes app_user_id nullable to support web-based payments where no
     * registered AppUser account exists — payers are identified by their
     * invoice / consumer record instead.
     */
    public function up(): void
    {
        Schema::table('app_user_payments', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['app_user_id']);

            // Re-add as nullable with foreign key
            $table->unsignedBigInteger('app_user_id')->nullable()->change();
            $table->foreign('app_user_id')->references('id')->on('app_users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_user_payments', function (Blueprint $table) {
            $table->dropForeign(['app_user_id']);
            $table->unsignedBigInteger('app_user_id')->nullable(false)->change();
            $table->foreign('app_user_id')->references('id')->on('app_users');
        });
    }
};
