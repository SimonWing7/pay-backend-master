<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * consumers was created with unique(merchant_id, email) and
     * unique(merchant_id, mobile_number), so one parent could only ever be one
     * record. A school needs one record per child (each gets their own
     * invoice), so the same parent contact is now allowed once per student.
     * The new rules go in first: MySQL needs an index leading with merchant_id
     * to stay in place for the foreign key while the old ones are dropped.
     */
    public function up(): void
    {
        Schema::table('consumers', function (Blueprint $table) {
            $table->unique(['merchant_id', 'email', 'student_name'], 'consumers_merchant_email_student_unique');
            $table->unique(['merchant_id', 'mobile_number', 'student_name'], 'consumers_merchant_mobile_student_unique');
        });

        Schema::table('consumers', function (Blueprint $table) {
            $table->dropUnique(['merchant_id', 'email']);
            $table->dropUnique(['merchant_id', 'mobile_number']);
        });
    }

    /**
     * Only safe while no parent has more than one record — once siblings
     * exist this will (rightly) refuse to run.
     */
    public function down(): void
    {
        Schema::table('consumers', function (Blueprint $table) {
            $table->unique(['merchant_id', 'email']);
            $table->unique(['merchant_id', 'mobile_number']);
        });

        Schema::table('consumers', function (Blueprint $table) {
            $table->dropUnique('consumers_merchant_email_student_unique');
            $table->dropUnique('consumers_merchant_mobile_student_unique');
        });
    }
};
