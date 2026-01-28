<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if a foreign key exists.
     */
    protected function foreignKeyExists(string $table, string $keyName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $result = $connection->selectOne(
            "SELECT COUNT(*) as count 
             FROM information_schema.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = ? 
             AND CONSTRAINT_NAME = ?",
            [$database, $table, $keyName]
        );
        
        return $result->count > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add new columns
        Schema::table('enrolled_students', function (Blueprint $table) {
            // Add acad_id column (FK to academic_calendar)
            $table->unsignedBigInteger('acad_id')->nullable()->after('student_id');
            
            // Add course_id column (FK to courses)  
            $table->unsignedBigInteger('course_id')->nullable()->after('acad_id');
        });

        // Add foreign key constraints (nullable columns are allowed in foreign keys)
        // Check if foreign keys already exist before adding
        $acadIdFkExists = $this->foreignKeyExists('enrolled_students', 'fk_enrolled_students_acad_id');
        $courseIdFkExists = $this->foreignKeyExists('enrolled_students', 'fk_enrolled_students_course_id');
        
        Schema::table('enrolled_students', function (Blueprint $table) use ($acadIdFkExists, $courseIdFkExists) {
            if (!$acadIdFkExists) {
                $table->foreign('acad_id', 'fk_enrolled_students_acad_id')
                    ->references('calendar_id')
                    ->on('academic_calendar')
                    ->onDelete('restrict');
            }
            
            if (!$courseIdFkExists) {
                $table->foreign('course_id', 'fk_enrolled_students_course_id')
                    ->references('course_id')
                    ->on('courses')
                    ->onDelete('restrict');
            }
        });

        // Rename status column to enrollment_status using raw SQL (more compatible)
        // Check if column exists before renaming to avoid errors
        if (Schema::hasColumn('enrolled_students', 'status')) {
            DB::statement('ALTER TABLE `enrolled_students` CHANGE `status` `enrollment_status` VARCHAR(255) DEFAULT "enrolled"');
        }

        // Add unique constraint on (student_id, acad_id)
        // Note: MySQL allows multiple NULL values in unique constraints, so existing NULL acad_id records won't conflict
        Schema::table('enrolled_students', function (Blueprint $table) {
            $table->unique(['student_id', 'acad_id'], 'unique_student_acad');
        });

        // Note: Existing academic_year data will remain for reference
        // acad_id should be populated when creating new enrollments
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrolled_students', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('unique_student_acad');
            
            // Drop foreign keys
            $table->dropForeign('fk_enrolled_students_acad_id');
            $table->dropForeign('fk_enrolled_students_course_id');
        });

        // Drop columns
        Schema::table('enrolled_students', function (Blueprint $table) {
            $table->dropColumn(['acad_id', 'course_id']);
        });

        // Rename column back using raw SQL
        if (Schema::hasColumn('enrolled_students', 'enrollment_status')) {
            DB::statement('ALTER TABLE `enrolled_students` CHANGE `enrollment_status` `status` VARCHAR(255) DEFAULT "enrolled"');
        }
    }
};

