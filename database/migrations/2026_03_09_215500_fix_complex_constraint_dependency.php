<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the foreign key that depends on the index
        try {
            DB::statement("ALTER TABLE enrolled_students DROP FOREIGN KEY `fk_enrolled_students_acad_id`");
            Log::info('Dropped fk_enrolled_students_acad_id');
        } catch (\Exception $e) {
            Log::info('Could not drop FK fk_enrolled_students_acad_id (may not exist): ' . $e->getMessage());
        }

        // 2. Now drop the broken unique index
        try {
            DB::statement("ALTER TABLE enrolled_students DROP INDEX `unique_student_acad`");
            Log::info('Dropped unique_student_acad index');
        } catch (\Exception $e) {
            Log::info('Could not drop unique_student_acad (may not exist): ' . $e->getMessage());
        }

        // 3. Create a normal index for acad_id so the foreign key can use it
        try {
            DB::statement("ALTER TABLE enrolled_students ADD INDEX `idx_enrolled_students_acad_id` (`acad_id`)");
            Log::info('Added normal index for acad_id');
        } catch (\Exception $e) {
            Log::warning('Could not add normal index for acad_id (might already exist): ' . $e->getMessage());
        }

        // 4. Recreate the foreign key
        try {
            DB::statement("
                ALTER TABLE enrolled_students 
                ADD CONSTRAINT `fk_enrolled_students_acad_id` 
                FOREIGN KEY (`acad_id`) REFERENCES `academic_calendar` (`calendar_id`) 
                ON DELETE RESTRICT
            ");
            Log::info('Recreated fk_enrolled_students_acad_id');
        } catch (\Exception $e) {
            Log::error('Could not recreate FK fk_enrolled_students_acad_id: ' . $e->getMessage());
        }

        // 5. Finally, recreate the unique constraint properly
        try {
            DB::statement("ALTER TABLE enrolled_students ADD UNIQUE `unique_student_acad` (`student_number`, `acad_id`)");
            Log::info('Recreated unique_student_acad on (student_number, acad_id)');
        } catch (\Exception $e) {
            Log::error('Could not recreate unique_student_acad: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        // Not needed for a hotfix
    }
};
