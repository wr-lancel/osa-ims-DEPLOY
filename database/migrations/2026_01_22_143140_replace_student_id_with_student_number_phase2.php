<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Helper method to safely drop foreign key if it exists
     */
    protected function dropForeignKeyIfExists(string $table, string $fkName): void
    {
        try {
            // First check if it exists
            $fkExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table, $fkName]);
            
            if (!empty($fkExists)) {
                Schema::table($table, function (Blueprint $table) use ($fkName) {
                    $table->dropForeign($fkName);
                });
            }
        } catch (\Exception $e) {
            // Foreign key doesn't exist or can't be dropped, continue
        }
    }

    /**
     * Helper method to safely drop index if it exists
     */
    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexExists = DB::select("
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ", [$table, $indexName]);
        
        if (!empty($indexExists)) {
            try {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            } catch (\Exception $e) {
                // Continue if it fails
            }
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Drop all foreign key constraints on student_id
        
        // Users table
        if (Schema::hasColumn('users', 'student_id')) {
            // Check if foreign key exists before dropping
            $fkExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'users' 
                AND COLUMN_NAME = 'student_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
                AND CONSTRAINT_NAME = 'fk_users_student_id'
            ");
            if (!empty($fkExists)) {
                try {
                    Schema::table('users', function (Blueprint $table) {
                        $table->dropForeign('fk_users_student_id');
                    });
                } catch (\Exception $e) {
                    // Continue if it fails
                }
            }
            
            $this->dropIndexIfExists('users', 'unique_users_student_id');
        }

        // Enrolled Students table
        if (Schema::hasColumn('enrolled_students', 'student_id')) {
            // Drop foreign key if it exists
            try {
                Schema::table('enrolled_students', function (Blueprint $table) {
                    $table->dropForeign('fk_enrolled_students_student_id');
                });
            } catch (\Exception $e) {
                // Foreign key might not exist or have different name, try to find and drop it
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'enrolled_students' 
                    AND COLUMN_NAME = 'student_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($foreignKeys as $fk) {
                    try {
                        Schema::table('enrolled_students', function (Blueprint $table) use ($fk) {
                            $table->dropForeign($fk->CONSTRAINT_NAME);
                        });
                    } catch (\Exception $e2) {
                        // Continue if it fails
                    }
                }
            }
            
            // Drop index if it exists
            try {
                Schema::table('enrolled_students', function (Blueprint $table) {
                    $table->dropIndex('idx_enrolled_students_student_id');
                });
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
        }

        // Sports Borrowing table
        if (Schema::hasColumn('sports_borrowing', 'student_id')) {
            $this->dropForeignKeyIfExists('sports_borrowing', 'fk_sports_borrowing_student_id');
            $this->dropIndexIfExists('sports_borrowing', 'idx_sports_borrowing_student_id');
        }

        // Discipline table
        if (Schema::hasColumn('discipline', 'student_id')) {
            $this->dropForeignKeyIfExists('discipline', 'fk_discipline_student_id');
            $this->dropIndexIfExists('discipline', 'idx_discipline_student_id');
        }

        // Org Members table
        if (Schema::hasColumn('org_members', 'student_id')) {
            $this->dropForeignKeyIfExists('org_members', 'fk_org_members_student_id');
            $this->dropIndexIfExists('org_members', 'idx_org_members_student_id');
        }

        // Org Officers table
        if (Schema::hasColumn('org_officers', 'student_id')) {
            $this->dropForeignKeyIfExists('org_officers', 'fk_org_officers_student_id');
            $this->dropIndexIfExists('org_officers', 'idx_org_officers_student_id');
        }

        // Violation Summary table
        if (Schema::hasColumn('violation_summary', 'student_id')) {
            $this->dropForeignKeyIfExists('violation_summary', 'fk_violation_summary_student_id');
            $this->dropIndexIfExists('violation_summary', 'idx_violation_summary_student_id');
        }

        // Risk Prediction table
        if (Schema::hasColumn('risk_prediction', 'student_id')) {
            $this->dropForeignKeyIfExists('risk_prediction', 'fk_risk_prediction_student_id');
            $this->dropIndexIfExists('risk_prediction', 'idx_risk_prediction_student_id');
        }

        // Student Profiles table
        if (Schema::hasColumn('student_profiles', 'student_id')) {
            $this->dropForeignKeyIfExists('student_profiles', 'student_profiles_student_id_foreign');
        }

        // Student Educational Backgrounds table
        if (Schema::hasColumn('student_educational_backgrounds', 'student_id')) {
            $this->dropForeignKeyIfExists('student_educational_backgrounds', 'student_educational_backgrounds_student_id_foreign');
        }

        // Student Family Info table
        if (Schema::hasColumn('student_family_info', 'student_id')) {
            $this->dropForeignKeyIfExists('student_family_info', 'student_family_info_student_id_foreign');
        }

        // Student Emergency Contacts table
        if (Schema::hasColumn('student_emergency_contacts', 'student_id')) {
            $this->dropForeignKeyIfExists('student_emergency_contacts', 'student_emergency_contacts_student_id_foreign');
        }

        // Step 2: Drop student_id columns from all tables
        $tablesToDrop = [
            'users', 'enrolled_students', 'sports_borrowing', 'discipline',
            'org_members', 'org_officers', 'violation_summary', 'risk_prediction',
            'student_profiles', 'student_educational_backgrounds', 'student_family_info', 'student_emergency_contacts'
        ];
        
        foreach ($tablesToDrop as $table) {
            if (Schema::hasColumn($table, 'student_id')) {
                try {
                    Schema::table($table, function (Blueprint $table) {
                        $table->dropColumn('student_id');
                    });
                } catch (\Exception $e) {
                    // Column might not exist or already dropped, continue
                }
            }
        }

        // Step 3: Change students table primary key from student_id to student_number
        if (Schema::hasColumn('students', 'student_id')) {
            // First, check if student_id is auto-increment and remove it
            $columnInfo = DB::select("
                SELECT EXTRA 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'students' 
                AND COLUMN_NAME = 'student_id'
            ");
            
            if (!empty($columnInfo) && strpos($columnInfo[0]->EXTRA, 'auto_increment') !== false) {
                // Remove auto-increment by modifying the column
                try {
                    DB::statement('ALTER TABLE students MODIFY student_id BIGINT UNSIGNED NOT NULL');
                } catch (\Exception $e) {
                    // Continue if it fails
                }
            }
            
            // Drop the primary key on student_id
            try {
                DB::statement('ALTER TABLE students DROP PRIMARY KEY');
            } catch (\Exception $e) {
                // Primary key might not exist or already dropped, continue
            }
            
            // Drop the student_id column
            try {
                Schema::table('students', function (Blueprint $table) {
                    $table->dropColumn('student_id');
                });
            } catch (\Exception $e) {
                // Column might already be dropped, continue
            }
        }

        // Make student_number the primary key (it already has unique constraint, but we need to make it primary)
        // Check if student_number is already the primary key
        $isPrimary = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'students' 
            AND COLUMN_NAME = 'student_number' 
            AND CONSTRAINT_NAME = 'PRIMARY'
        ");
        
        if (empty($isPrimary)) {
            try {
                DB::statement('ALTER TABLE students ADD PRIMARY KEY (student_number)');
            } catch (\Exception $e) {
                // Primary key might already exist, continue
            }
        }

        // Step 4: Make student_number NOT NULL in all tables that reference it
        // Note: users.student_number stays nullable because not all users are students
        // No change needed for users table - keep it nullable

        Schema::table('enrolled_students', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        Schema::table('sports_borrowing', function (Blueprint $table) {
            // Keep nullable since it can be null (employee_id can be used instead)
        });

        Schema::table('discipline', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        Schema::table('org_members', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        Schema::table('org_officers', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        Schema::table('violation_summary', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        Schema::table('risk_prediction', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        Schema::table('student_educational_backgrounds', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        Schema::table('student_family_info', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        Schema::table('student_emergency_contacts', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable(false)->change();
        });

        // Step 5: Add foreign key constraints on student_number
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_users_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('enrolled_students', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_enrolled_students_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('sports_borrowing', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_sports_borrowing_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('set null');
        });

        Schema::table('discipline', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_discipline_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('org_members', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_org_members_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('org_officers', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_org_officers_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('violation_summary', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_violation_summary_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('risk_prediction', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_risk_prediction_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_student_profiles_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('student_educational_backgrounds', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_student_educational_backgrounds_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('student_family_info', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_student_family_info_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        Schema::table('student_emergency_contacts', function (Blueprint $table) {
            $table->foreign('student_number', 'fk_student_emergency_contacts_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('cascade');
        });

        // Step 6: Remove employee_id from users table
        Schema::table('users', function (Blueprint $table) {
            // Check if foreign key exists before dropping
            if (DB::getDriverName() === 'mysql') {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'users' 
                    AND COLUMN_NAME = 'employee_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            }
            $table->dropColumn('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This rollback is complex and may not fully restore the original state
     * if student_id values were auto-increment. A full backup restore is recommended.
     */
    public function down(): void
    {
        // Drop foreign keys on student_number (safely)
        $this->dropForeignKeyIfExists('users', 'fk_users_student_number');
        $this->dropForeignKeyIfExists('enrolled_students', 'fk_enrolled_students_student_number');
        $this->dropForeignKeyIfExists('sports_borrowing', 'fk_sports_borrowing_student_number');
        $this->dropForeignKeyIfExists('discipline', 'fk_discipline_student_number');
        $this->dropForeignKeyIfExists('org_members', 'fk_org_members_student_number');
        $this->dropForeignKeyIfExists('org_officers', 'fk_org_officers_student_number');
        $this->dropForeignKeyIfExists('violation_summary', 'fk_violation_summary_student_number');
        $this->dropForeignKeyIfExists('risk_prediction', 'fk_risk_prediction_student_number');
        $this->dropForeignKeyIfExists('student_profiles', 'fk_student_profiles_student_number');
        $this->dropForeignKeyIfExists('student_educational_backgrounds', 'fk_student_educational_backgrounds_student_number');
        $this->dropForeignKeyIfExists('student_family_info', 'fk_student_family_info_student_number');
        $this->dropForeignKeyIfExists('student_emergency_contacts', 'fk_student_emergency_contacts_student_number');

        // Re-add student_id as auto-increment primary key to students table
        if (!Schema::hasColumn('students', 'student_id')) {
            // Drop current primary key on student_number first
            try {
                DB::statement('ALTER TABLE students DROP PRIMARY KEY, ADD student_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
            } catch (\Exception $e) {
                // Fallback: try adding column then setting primary key
                try {
                    Schema::table('students', function (Blueprint $table) {
                        $table->id('student_id')->first();
                    });
                } catch (\Exception $e2) {
                    // Continue
                }
            }
        }

        // Re-add employee_id to users table if it doesn't exist
        if (!Schema::hasColumn('users', 'employee_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('user_id');
            });
        }
    }

};
