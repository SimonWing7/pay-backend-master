<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            // Where fee statements and charge results are emailed. NULL means
            // "same as the notification email", so it keeps following it.
            $table->string('billing_email')->nullable()->after('notification_email');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('billing_email');
        });
    }
};
