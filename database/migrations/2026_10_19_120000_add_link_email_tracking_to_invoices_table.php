<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Whether the payer has been emailed their payment link:
            // not_sent | queued | sent | failed. NULL means the invoice
            // predates this tracking and is treated as already sent.
            $table->string('link_email_status', 16)->nullable()->after('link_type');
            $table->timestamp('link_emailed_at')->nullable()->after('link_email_status');
            $table->string('link_email_error', 255)->nullable()->after('link_emailed_at');

            // The background sender looks these up by status.
            $table->index(['merchant_id', 'link_email_status']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['merchant_id', 'link_email_status']);
            $table->dropColumn(['link_email_status', 'link_emailed_at', 'link_email_error']);
        });
    }
};
