<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Type of respondent: student, employee, or other (free text)
            $table->string('respondent_type')->nullable()->after('respondent_enrolled_id');

            // When respondent_type = 'employee'
            $table->unsignedBigInteger('respondent_employee_id')->nullable()->after('respondent_type');

            // When respondent_type = 'other' (free text name)
            $table->string('respondent_name')->nullable()->after('respondent_employee_id');

            $table->foreign('respondent_employee_id', 'fk_complaints_respondent_employee')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign('fk_complaints_respondent_employee');
            $table->dropColumn(['respondent_type', 'respondent_employee_id', 'respondent_name']);
        });
    }
};
