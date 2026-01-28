<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->after('employee_id');
            
            $table->foreign('student_id', 'fk_users_student_id')
                ->references('student_id')
                ->on('students')
                ->onDelete('cascade');
            
            $table->unique('student_id', 'unique_users_student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_student_id');
            $table->dropUnique('unique_users_student_id');
            $table->dropColumn('student_id');
        });
    }
};

