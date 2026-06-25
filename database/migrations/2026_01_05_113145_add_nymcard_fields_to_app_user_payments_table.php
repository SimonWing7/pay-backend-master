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
            $table->string('nymcard_resource_id')->nullable()->after('token');
            $table->text('nymcard_token')->nullable()->after('nymcard_resource_id');
            $table->json('nymcard_metadata')->nullable()->after('nymcard_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_user_payments', function (Blueprint $table) {
            $table->dropColumn(['nymcard_resource_id', 'nymcard_token', 'nymcard_metadata']);
        });
    }
};
