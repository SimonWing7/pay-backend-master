<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['consumer_id']);
            $table->foreignId('consumer_id')->nullable()->change()->constrained('consumers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['consumer_id']);
            $table->foreignId('consumer_id')->nullable(false)->change()->constrained('consumers');
        });
    }
};
