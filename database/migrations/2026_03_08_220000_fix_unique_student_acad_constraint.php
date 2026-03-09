<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Fix: The old unique constraint 'unique_student_acad' was on (student_id, acad_id),
     * but student_id was dropped. MySQL collapsed it to just UNIQUE(acad_id),
     * meaning only 1 enrollment per term across ALL students.
     * This recreates it correctly on (student_number, acad_id).
     */
    public function up(): void
    {
        // Drop ALL unique constraints on enrolled_students to clean up
        try {
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'enrolled_students' 
                AND CONSTRAINT_TYPE = 'UNIQUE'
            ");

            foreach ($constraints as $constraint) {
                // Don't drop PRIMARY
                if ($constraint->CONSTRAINT_NAME !== 'PRIMARY') {
                    try {
                        DB::statement("ALTER TABLE enrolled_students DROP INDEX `{$constraint->CONSTRAINT_NAME}`");
                        Log::info("Dropped constraint: {$constraint->CONSTRAINT_NAME}");
                    } catch (\Exception $e) {
                        Log::warning("Could not drop {$constraint->CONSTRAINT_NAME}: " . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error cleaning up constraints: ' . $e->getMessage());
        }

        // Only add the constraint if it doesn't already exist
        $exists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'enrolled_students' 
            AND CONSTRAINT_TYPE = 'UNIQUE'
            AND CONSTRAINT_NAME = 'unique_student_acad'
        ");

        if (empty($exists)) {
            Schema::table('enrolled_students', function (Blueprint $table) {
                $table->unique(['student_number', 'acad_id'], 'unique_student_acad');
            });
            Log::info('Recreated unique_student_acad on (student_number, acad_id)');
        } else {
            Log::info('unique_student_acad already exists, skipping creation.');
        }
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
