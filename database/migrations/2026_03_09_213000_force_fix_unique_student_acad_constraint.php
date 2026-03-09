<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the broken unique constraint (may be collapsed to just acad_id)
        try {
            DB::statement("ALTER TABLE enrolled_students DROP INDEX `unique_student_acad`");
            Log::info('Dropped unique_student_acad index.');
        } catch (\Exception $e) {
            Log::info('Could not drop unique_student_acad (may not exist): ' . $e->getMessage());
        }

        // Recreate correctly on (student_number, acad_id)
        try {
            DB::statement("ALTER TABLE enrolled_students ADD UNIQUE `unique_student_acad` (`student_number`, `acad_id`)");
            Log::info('Recreated unique_student_acad on (student_number, acad_id).');
        } catch (\Exception $e) {
            Log::error('Could not recreate unique_student_acad: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE enrolled_students DROP INDEX `unique_student_acad`");
        } catch (\Exception $e) {
            //
        }
    }
};
