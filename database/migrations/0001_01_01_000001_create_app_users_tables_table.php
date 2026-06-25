<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('device_id')->unique();
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->longText('meta');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('app_user_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained();
            $table->foreignId('invoice_id')->constrained();
            $table->string('token');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_users');
    }
};
