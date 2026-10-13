<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_charge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_statement_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number');
            $table->string('cartid');
            $table->decimal('amount', 12, 2);
            // pending (call in flight) | authorised | declined | error | unknown
            $table->string('outcome')->default('pending');
            $table->string('telr_tran_ref')->nullable();
            $table->string('telr_code')->nullable();
            $table->string('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_charge_attempts');
    }
};
