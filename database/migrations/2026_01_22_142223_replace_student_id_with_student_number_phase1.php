<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Helper method to safely add student_number column
     */
    protected function addStudentNumberColumn(string $tableName): void
    {
        if (!Schema::hasColumn($tableName, 'student_number')) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'student_id')) {
                    $table->string('student_number', 50)->nullable()->after('student_id');
                } else {
                    $table->string('student_number', 50)->nullable();
                }
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add student_number columns to all tables that have student_id
        // Keep old student_id columns temporarily for data migration

        // 1. Users table
        $this->addStudentNumberColumn('users');

        // 2. Enrolled Students table
        $this->addStudentNumberColumn('enrolled_students');

        // 3. Sports Borrowing table
        $this->addStudentNumberColumn('sports_borrowing');

        // 4. Discipline table
        $this->addStudentNumberColumn('discipline');

        // 5. Org Members table
        $this->addStudentNumberColumn('org_members');

        // 6. Org Officers table
        $this->addStudentNumberColumn('org_officers');

        // 7. Violation Summary table
        $this->addStudentNumberColumn('violation_summary');

        // 8. Risk Prediction table
        $this->addStudentNumberColumn('risk_prediction');

        // 9. Student Profiles table
        $this->addStudentNumberColumn('student_profiles');

        // 10. Student Educational Backgrounds table
        $this->addStudentNumberColumn('student_educational_backgrounds');

        // 11. Student Family Info table
        $this->addStudentNumberColumn('student_family_info');

        // 12. Student Emergency Contacts table
        $this->addStudentNumberColumn('student_emergency_contacts');

        // Step 2: Populate student_number from students table via JOIN
        if (Schema::hasColumn('users', 'student_id')) {
            DB::statement("
                UPDATE users u
                INNER JOIN students s ON u.student_id = s.student_id
                SET u.student_number = s.student_number
                WHERE u.student_id IS NOT NULL
            ");
        }

        if (Schema::hasColumn('students', 'student_id') && Schema::hasColumn('enrolled_students', 'student_id') && Schema::hasColumn('enrolled_students', 'student_number')) {
            DB::statement("
                UPDATE enrolled_students e
                INNER JOIN students s ON e.student_id = s.student_id
                SET e.student_number = s.student_number
                WHERE e.student_id IS NOT NULL AND e.student_number IS NULL
            ");
        }

        // Check if students.student_id exists before running UPDATEs
        $studentsHasStudentId = Schema::hasColumn('students', 'student_id');
        
        if ($studentsHasStudentId && Schema::hasColumn('sports_borrowing', 'student_id') && Schema::hasColumn('sports_borrowing', 'student_number')) {
            DB::statement("
                UPDATE sports_borrowing sb
                INNER JOIN students s ON sb.student_id = s.student_id
                SET sb.student_number = s.student_number
                WHERE sb.student_id IS NOT NULL AND sb.student_number IS NULL
            ");
        }

        if ($studentsHasStudentId && Schema::hasColumn('discipline', 'student_id') && Schema::hasColumn('discipline', 'student_number')) {
            DB::statement("
                UPDATE discipline d
                INNER JOIN students s ON d.student_id = s.student_id
                SET d.student_number = s.student_number
                WHERE d.student_id IS NOT NULL AND d.student_number IS NULL
            ");
        }

        if ($studentsHasStudentId && Schema::hasColumn('org_members', 'student_id') && Schema::hasColumn('org_members', 'student_number')) {
            DB::statement("
                UPDATE org_members om
                INNER JOIN students s ON om.student_id = s.student_id
                SET om.student_number = s.student_number
                WHERE om.student_id IS NOT NULL AND om.student_number IS NULL
            ");
        }

        if ($studentsHasStudentId && Schema::hasColumn('org_officers', 'student_id') && Schema::hasColumn('org_officers', 'student_number')) {
            DB::statement("
                UPDATE org_officers oo
                INNER JOIN students s ON oo.student_id = s.student_id
                SET oo.student_number = s.student_number
                WHERE oo.student_id IS NOT NULL AND oo.student_number IS NULL
            ");
        }

        if ($studentsHasStudentId && Schema::hasColumn('violation_summary', 'student_id') && Schema::hasColumn('violation_summary', 'student_number')) {
            DB::statement("
                UPDATE violation_summary vs
                INNER JOIN students s ON vs.student_id = s.student_id
                SET vs.student_number = s.student_number
                WHERE vs.student_id IS NOT NULL AND vs.student_number IS NULL
            ");
        }

        if ($studentsHasStudentId && Schema::hasColumn('risk_prediction', 'student_id') && Schema::hasColumn('risk_prediction', 'student_number')) {
            DB::statement("
                UPDATE risk_prediction rp
                INNER JOIN students s ON rp.student_id = s.student_id
                SET rp.student_number = s.student_number
                WHERE rp.student_id IS NOT NULL AND rp.student_number IS NULL
            ");
        }

        if ($studentsHasStudentId && Schema::hasColumn('student_profiles', 'student_id') && Schema::hasColumn('student_profiles', 'student_number')) {
            DB::statement("
                UPDATE student_profiles sp
                INNER JOIN students s ON sp.student_id = s.student_id
                SET sp.student_number = s.student_number
                WHERE sp.student_id IS NOT NULL AND sp.student_number IS NULL
            ");
        }

        if ($studentsHasStudentId && Schema::hasColumn('student_educational_backgrounds', 'student_id') && Schema::hasColumn('student_educational_backgrounds', 'student_number')) {
            DB::statement("
                UPDATE student_educational_backgrounds seb
                INNER JOIN students s ON seb.student_id = s.student_id
                SET seb.student_number = s.student_number
                WHERE seb.student_id IS NOT NULL AND seb.student_number IS NULL
            ");
        }

        if ($studentsHasStudentId && Schema::hasColumn('student_family_info', 'student_id') && Schema::hasColumn('student_family_info', 'student_number')) {
            DB::statement("
                UPDATE student_family_info sfi
                INNER JOIN students s ON sfi.student_id = s.student_id
                SET sfi.student_number = s.student_number
                WHERE sfi.student_id IS NOT NULL AND sfi.student_number IS NULL
            ");
        }

        if ($studentsHasStudentId && Schema::hasColumn('student_emergency_contacts', 'student_id') && Schema::hasColumn('student_emergency_contacts', 'student_number')) {
            DB::statement("
                UPDATE student_emergency_contacts sec
                INNER JOIN students s ON sec.student_id = s.student_id
                SET sec.student_number = s.student_number
                WHERE sec.student_id IS NOT NULL AND sec.student_number IS NULL
            ");
        }

        // Step 3: Add indexes on student_number columns
        Schema::table('users', function (Blueprint $table) {
            $table->index('student_number', 'idx_users_student_number');
        });

        Schema::table('enrolled_students', function (Blueprint $table) {
            $table->index('student_number', 'idx_enrolled_students_student_number');
        });

        Schema::table('sports_borrowing', function (Blueprint $table) {
            $table->index('student_number', 'idx_sports_borrowing_student_number');
        });

        Schema::table('discipline', function (Blueprint $table) {
            $table->index('student_number', 'idx_discipline_student_number');
        });

        Schema::table('org_members', function (Blueprint $table) {
            $table->index('student_number', 'idx_org_members_student_number');
        });

        Schema::table('org_officers', function (Blueprint $table) {
            $table->index('student_number', 'idx_org_officers_student_number');
        });

        Schema::table('violation_summary', function (Blueprint $table) {
            $table->index('student_number', 'idx_violation_summary_student_number');
        });

        Schema::table('risk_prediction', function (Blueprint $table) {
            $table->index('student_number', 'idx_risk_prediction_student_number');
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->index('student_number', 'idx_student_profiles_student_number');
        });

        Schema::table('student_educational_backgrounds', function (Blueprint $table) {
            $table->index('student_number', 'idx_student_educational_backgrounds_student_number');
        });

        Schema::table('student_family_info', function (Blueprint $table) {
            $table->index('student_number', 'idx_student_family_info_student_number');
        });

        Schema::table('student_emergency_contacts', function (Blueprint $table) {
            $table->index('student_number', 'idx_student_emergency_contacts_student_number');
        });

        // Step 4: Add foreign key constraints on student_number
        // Note: We can't add foreign keys yet because students.student_number is not the primary key
        // This will be done in Phase 2 after we change the primary key
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('enrolled_students', function (Blueprint $table) {
            $table->dropIndex('idx_enrolled_students_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('sports_borrowing', function (Blueprint $table) {
            $table->dropIndex('idx_sports_borrowing_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('discipline', function (Blueprint $table) {
            $table->dropIndex('idx_discipline_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('org_members', function (Blueprint $table) {
            $table->dropIndex('idx_org_members_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('org_officers', function (Blueprint $table) {
            $table->dropIndex('idx_org_officers_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('violation_summary', function (Blueprint $table) {
            $table->dropIndex('idx_violation_summary_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('risk_prediction', function (Blueprint $table) {
            $table->dropIndex('idx_risk_prediction_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_student_profiles_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('student_educational_backgrounds', function (Blueprint $table) {
            $table->dropIndex('idx_student_educational_backgrounds_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('student_family_info', function (Blueprint $table) {
            $table->dropIndex('idx_student_family_info_student_number');
            $table->dropColumn('student_number');
        });

        Schema::table('student_emergency_contacts', function (Blueprint $table) {
            $table->dropIndex('idx_student_emergency_contacts_student_number');
            $table->dropColumn('student_number');
        });
    }
};
