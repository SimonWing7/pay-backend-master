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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('consumers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->unique(['merchant_id', 'email']);
            $table->unique(['merchant_id', 'mobile_number']);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid()->index();
            $table->string('name');
            $table->longText('description');
            $table->double('fee');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid()->index();
//            $table->unsignedBigInteger('number');
            $table->double('total_fee');
            $table->foreignId('consumer_id')->constrained();
            $table->foreignId('merchant_id')->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('product_id')->constrained('products');
            $table->double('fee');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumers');
        Schema::dropIfExists('merchants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('invoice_details');
        Schema::dropIfExists('invoices');
    }
};
