<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop student_id column if it exists (it referenced a non-existent students.student_id)
        if (Schema::hasColumn('risk_prediction', 'student_id')) {
            Schema::table('risk_prediction', function (Blueprint $table) {
                $table->dropColumn('student_id');
            });
        }

        // Add student_number if it doesn't exist yet
        if (!Schema::hasColumn('risk_prediction', 'student_number')) {
            Schema::table('risk_prediction', function (Blueprint $table) {
                $table->string('student_number')->nullable()->after('prediction_id');
                $table->foreign('student_number', 'fk_risk_pred_student_number')
                    ->references('student_number')
                    ->on('students')
                    ->onDelete('cascade');
                $table->index('student_number', 'idx_risk_pred_student_number');
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if (Schema::hasColumn('risk_prediction', 'student_number')) {
            Schema::table('risk_prediction', function (Blueprint $table) {
                $table->dropForeign('fk_risk_pred_student_number');
                $table->dropIndex('idx_risk_pred_student_number');
                $table->dropColumn('student_number');
            });
        }

        if (!Schema::hasColumn('risk_prediction', 'student_id')) {
            Schema::table('risk_prediction', function (Blueprint $table) {
                $table->unsignedBigInteger('student_id')->nullable()->after('prediction_id');
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
