<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            // Internal label only, shown in the merchant's own dropdowns —
            // branding is unified across entities, so this is never shown
            // to payers on the public checkout pages.
            $table->string('name');
            $table->string('lean_destination_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_entities');
    }
};
