<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_billing_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            // Telr transaction reference of the authorised 3-D Secure sale
            // that later continuous-authority charges refer to. Encrypted via
            // the model cast. No card number is ever stored — Telr hosts the
            // card entry.
            $table->text('telr_tran_ref')->nullable();
            $table->string('card_type')->nullable();
            $table->string('last4', 4)->nullable();
            $table->unsignedTinyInteger('expiry_month')->nullable();
            $table->unsignedSmallInteger('expiry_year')->nullable();
            // pending (awaiting 3DS) | active | replaced | failed
            $table->string('status')->default('pending');
            $table->string('setup_cartid')->unique();
            $table->string('telr_order_ref')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('consent_ip', 64)->nullable();
            $table->timestamp('verification_refunded_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_billing_cards');
    }
};
