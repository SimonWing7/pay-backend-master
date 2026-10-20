<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // not_started | in_progress | submitted | changes_requested | approved
        //
        // Defaults to 'approved' on purpose: every merchant that exists today
        // is already live and must stay that way, and any other code path that
        // creates a merchant keeps working. Only the admin "new merchant" form
        // sets 'not_started', which is what starts the onboarding journey.
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('onboarding_status', 20)->default('approved')->after('is_active')->index();
        });

        Schema::create('merchant_onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->unique()->constrained()->cascadeOnDelete();
            // The step the merchant should land on when they come back.
            $table->string('current_step', 20)->default('welcome');
            // Draft answers per step: {business: {...}, bank: {...}, contact: {...}, fees: {...}}
            $table->json('data')->nullable();
            // Optional supporting documents, kept on the private disk.
            $table->string('signatory_id_path')->nullable();
            $table->string('bank_letter_path')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_accepted_ip', 64)->nullable();
            $table->string('terms_accepted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_onboardings');
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('onboarding_status');
        });
    }
};
