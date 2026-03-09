<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'employee_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('user_id');
                $table->foreign('employee_id', 'fk_users_employee_id')
                    ->references('employee_id')
                    ->on('employees')
                    ->onDelete('cascade');
                $table->index('employee_id', 'idx_users_employee_id');
            });
        }

        // Fix existing users: create employee records for non-student users
        // that have both student_number and employee_id null.
        $orphanUsers = DB::table('users')
            ->whereNull('student_number')
            ->whereNull('employee_id')
            ->get();

        foreach ($orphanUsers as $user) {
            $isStudent = DB::table('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.role_id')
                ->where('user_roles.user_id', $user->user_id)
                ->where('roles.role_name', 'student')
                ->exists();

            if ($isStudent) {
                $student = DB::table('students')
                    ->where('email', $user->email)
                    ->first();

                if (!$student) {
                    $emailPrefix = explode('@', $user->email)[0];
                    $studentNumber = $emailPrefix;
                    DB::table('students')->insert([
                        'student_number' => $studentNumber,
                        'first_name' => $emailPrefix,
                        'last_name' => 'User',
                        'email' => $user->email,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $student = DB::table('students')->where('student_number', $studentNumber)->first();
                }

                DB::table('users')
                    ->where('user_id', $user->user_id)
                    ->update(['student_number' => $student->student_number]);
            } else {
                $existing = DB::table('employees')
                    ->where('email', $user->email)
                    ->first();

                if ($existing) {
                    DB::table('users')
                        ->where('user_id', $user->user_id)
                        ->update(['employee_id' => $existing->employee_id]);
                } else {
                    $emailPrefix = explode('@', $user->email)[0];
                    $employeeId = DB::table('employees')->insertGetId([
                        'employee_number' => 'EMP-' . $user->user_id,
                        'first_name' => ucfirst($emailPrefix),
                        'last_name' => 'Staff',
                        'email' => $user->email,
                        'department' => 'OSA',
                        'position' => 'Staff',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('users')
                        ->where('user_id', $user->user_id)
                        ->update(['employee_id' => $employeeId]);
                }
            }
        }

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT chk_users_exactly_one_link
            CHECK (
                (student_number IS NOT NULL AND employee_id IS NULL)
                OR (student_number IS NULL AND employee_id IS NOT NULL)
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT chk_users_exactly_one_link");

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_employee_id');
            $table->dropIndex('idx_users_employee_id');
            $table->dropColumn('employee_id');
        });
    }
};
