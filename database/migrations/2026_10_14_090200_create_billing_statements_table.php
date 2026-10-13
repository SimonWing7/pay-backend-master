<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            // e.g. EP-202610-0007 (period + merchant id) — deterministic, so
            // the same month can never produce two different numbers.
            $table->string('number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('volume', 14, 2);
            $table->decimal('fee_percentage', 5, 2);
            $table->decimal('fee_amount', 12, 2);
            $table->string('currency', 3)->default('AED');
            // issued | paid | failed | awaiting_card | needs_review | void
            $table->string('status')->default('issued');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('charge_due_at');
            $table->timestamp('next_attempt_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('first_failed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('telr_tran_ref')->nullable();
            $table->string('paid_method')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('awaiting_card_notified_at')->nullable();
            $table->timestamps();

            // The guard against ever billing a merchant twice for one month.
            $table->unique(['merchant_id', 'period_start']);
            $table->index(['status', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_statements');
    }
};
