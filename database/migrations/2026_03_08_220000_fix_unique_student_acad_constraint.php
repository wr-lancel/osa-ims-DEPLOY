<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fix: The old unique constraint 'unique_student_acad' was on (student_id, acad_id),
     * but student_id was dropped and replaced with student_number.
     * This migration drops the stale constraint and recreates it on (student_number, acad_id).
     */
    public function up(): void
    {
        // Drop the old constraint if it still exists
        try {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'enrolled_students' 
                AND CONSTRAINT_TYPE = 'UNIQUE'
                AND CONSTRAINT_NAME = 'unique_student_acad'
            ");

            if (count($constraints) > 0) {
                DB::statement("ALTER TABLE enrolled_students DROP INDEX `unique_student_acad`");
                Log::info('Dropped stale unique_student_acad constraint.');
            }
        } catch (\Exception $e) {
            Log::info('Could not drop unique_student_acad: ' . $e->getMessage());
        }

        // Recreate the unique constraint on (student_number, acad_id)
        Schema::table('enrolled_students', function (Blueprint $table) {
            $table->unique(['student_number', 'acad_id'], 'unique_student_acad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrolled_students', function (Blueprint $table) {
            $table->dropUnique('unique_student_acad');
        });
    }
};
