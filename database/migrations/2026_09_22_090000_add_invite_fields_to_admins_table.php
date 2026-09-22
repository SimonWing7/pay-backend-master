<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            // 'role' isn't enforced anywhere yet — every admin has the same
            // full access regardless of value. It's here so a future
            // permission system has somewhere to read from without another
            // migration; for now everyone is just 'admin'.
            $table->string('role')->default('admin')->after('email');
            $table->foreignId('invited_by')->nullable()->after('role')->constrained('admins')->nullOnDelete();
            $table->string('invite_token')->nullable()->unique()->after('invited_by');
            $table->timestamp('invited_at')->nullable()->after('invite_token');
            $table->timestamp('invite_accepted_at')->nullable()->after('invited_at');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by');
            $table->dropColumn(['role', 'invite_token', 'invited_at', 'invite_accepted_at']);
        });
    }
};
