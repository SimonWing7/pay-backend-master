<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // People a merchant has invited to work in their account. The merchant
        // record itself stays the account owner's login; these are additional
        // logins that act on the same merchant with a role.
        Schema::create('merchant_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Unique across all members. Also checked against merchants.email
            // in code, because sign-in looks up the merchant first.
            $table->string('email')->unique();
            $table->string('password')->nullable();   // NULL until the invite is accepted
            $table->string('role', 20);               // owner | staff | viewer
            // SHA-256 of the one-time set-password token (invite or reset).
            $table->string('setup_token_hash', 64)->nullable()->index();
            $table->timestamp('setup_token_expires_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->string('invited_by')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_users');
    }
};
