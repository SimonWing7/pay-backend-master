<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_entities', function (Blueprint $table) {
            $table->text('fallback_reference_note')->nullable()->after('fallback_account_name');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_entities', function (Blueprint $table) {
            $table->dropColumn('fallback_reference_note');
        });
    }
};
