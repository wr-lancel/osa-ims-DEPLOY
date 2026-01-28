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
     */
    public function up(): void
    {
        // Only drop student_id from enrolled_students if it exists
        if (Schema::hasColumn('enrolled_students', 'student_id')) {
            // First, try to drop any unique constraints that include student_id
            try {
                $uniqueConstraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'enrolled_students' 
                    AND CONSTRAINT_TYPE = 'UNIQUE'
                    AND CONSTRAINT_NAME LIKE '%student_id%'
                ");
                
                foreach ($uniqueConstraints as $constraint) {
                    try {
                        DB::statement("ALTER TABLE enrolled_students DROP INDEX `{$constraint->CONSTRAINT_NAME}`");
                    } catch (\Exception $e) {
                        Log::info("Could not drop unique constraint {$constraint->CONSTRAINT_NAME}: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                // Continue
            }
            
            // Get all foreign key constraint names that reference student_id
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'enrolled_students' 
                AND COLUMN_NAME = 'student_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            // Drop each foreign key using raw SQL
            foreach ($foreignKeys as $fk) {
                try {
                    DB::statement("ALTER TABLE enrolled_students DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Exception $e) {
                    Log::info("Could not drop foreign key {$fk->CONSTRAINT_NAME}: " . $e->getMessage());
                }
            }
            
            // Drop index if it exists
            $indexes = DB::select("
                SELECT DISTINCT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'enrolled_students' 
                AND COLUMN_NAME = 'student_id'
            ");
            
            foreach ($indexes as $idx) {
                try {
                    DB::statement("ALTER TABLE enrolled_students DROP INDEX `{$idx->INDEX_NAME}`");
                } catch (\Exception $e) {
                    Log::info("Could not drop index {$idx->INDEX_NAME}: " . $e->getMessage());
                }
            }
            
            // Make the column nullable first (in case it's NOT NULL)
            try {
                DB::statement("ALTER TABLE enrolled_students MODIFY student_id BIGINT UNSIGNED NULL");
            } catch (\Exception $e) {
                Log::info("Could not make student_id nullable: " . $e->getMessage());
            }
            
            // Drop the column using raw SQL
            try {
                DB::statement("ALTER TABLE enrolled_students DROP COLUMN student_id");
            } catch (\Exception $e) {
                Log::error("Could not drop student_id from enrolled_students: " . $e->getMessage());
                // Don't throw - let's see if we can continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't want to restore student_id as the migration is moving away from it
        // This migration is one-way
    }
};
