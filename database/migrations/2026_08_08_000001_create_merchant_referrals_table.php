<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('merchant_referrals', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_uuid')->index();
            $table->string('edfundo_user_id')->index();
            $table->string('edfundo_user_email')->nullable();

            // Registration event
            $table->timestamp('registered_at')->nullable();
            $table->json('registered_payload')->nullable();

            // Subscription event
            $table->string('subscription_plan')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->json('subscribed_payload')->nullable();

            // Credit-issued event
            $table->string('nymcard_transaction_ref')->nullable();
            $table->decimal('credit_amount', 8, 2)->nullable();
            $table->string('credit_currency')->nullable();
            $table->timestamp('credited_at')->nullable();
            $table->json('credit_payload')->nullable();

            // Commission tracking
            $table->enum('commission_status', ['pending', 'earned', 'settled'])->default('pending');
      $table->decimal('commission_amount', 8, 2)->default(50.00);
            $table->timestamp('commission_settled_at')->nullable();
            $table->string('commission_settled_by')->nullable();

            $table->timestamps();

            $table->unique(['merchant_uuid', 'edfundo_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_referrals');
    }
};
