<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use App\Models\EnrolledStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentAccountService
{
    protected string $emailDomain;

    public function __construct()
    {
        $this->emailDomain = config('app.student_email_domain', 'chcc.edu.ph');
    }

    /**
     * Create a user account for a student.
     * Email is always auto-generated from the student number following
     * the institutional format: {student_number}@chcc.edu.ph
     *
     * @param Student $student
     * @param string|null $email Ignored — always auto-generated
     * @param string|null $password Override password (if null, will use student_number)
     * @return User
     * @throws \Exception
     */
    public function createAccount(Student $student, ?string $email = null, ?string $password = null): User
    {
        // Check if account already exists
        if ($student->hasAccount()) {
            throw new \Exception("Student already has an account.");
        }

        // Always auto-generate email from student number (institutional format)
        $email = $this->generateEmail($student->student_number);

        // Generate password if not provided
        if (!$password) {
            $password = $this->generateDefaultPassword($student->student_number);
        }

        try {
            DB::beginTransaction();

            // Get student role
            $studentRole = Role::where('role_name', 'student')->first();
            if (!$studentRole) {
                throw new \Exception("Student role not found. Please run RoleSeeder.");
            }

            // Create user account
            $user = User::create([
                'email' => $email,
                'password' => $password,
                'student_number' => $student->student_number,
                'status' => 'active',
                'must_change_password' => true,
            ]);

            // Assign student role
            $user->roles()->attach($studentRole->role_id);

            DB::commit();

            Log::info("Student account created: student_number={$student->student_number}, email={$email}");

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create student account: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create account automatically when student is enrolled.
     *
     * @param EnrolledStudent $enrollment
     * @return User|null
     */
    public function createAccountFromEnrollment(EnrolledStudent $enrollment): ?User
    {
        // Check if auto-create is enabled
        $autoCreate = config('app.auto_create_student_accounts', false);
        if (!$autoCreate) {
            return null;
        }

        $student = $enrollment->student;
        if (!$student || $student->hasAccount()) {
            return null;
        }

        try {
            return $this->createAccount($student);
        } catch (\Exception $e) {
            Log::warning("Auto-create account failed for student {$student->student_number}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Bulk create accounts for multiple students.
     *
     * @param array $studentIds Array of student IDs
     * @return array{created: int, skipped: int, failed: int, errors: array}
     */
    public function bulkCreateAccounts(array $studentNumbers): array
    {
        $created = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($studentNumbers as $studentNumber) {
            try {
                $student = Student::find($studentNumber);
                if (!$student) {
                    $failed++;
                    $errors[] = [
                        'student_number' => $studentNumber,
                        'error' => 'Student not found',
                    ];
                    continue;
                }

                if ($student->hasAccount()) {
                    $skipped++;
                    continue;
                }

                $this->createAccount($student);
                $created++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'student_number' => $studentNumber,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Generate email address from student number.
     *
     * @param string $studentNumber
     * @return string
     */
    public function generateEmail(string $studentNumber): string
    {
        // Clean student number: remove spaces, convert to lowercase
        $cleanNumber = strtolower(trim($studentNumber));
        $cleanNumber = preg_replace('/[^a-z0-9-]/', '', $cleanNumber);

        $baseEmail = "{$cleanNumber}@{$this->emailDomain}";

        // Ensure uniqueness
        $email = $baseEmail;
        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = str_replace("@{$this->emailDomain}", "-{$counter}@{$this->emailDomain}", $baseEmail);
            $counter++;
        }

        return $email;
    }

    /**
     * Generate default password from student number.
     *
     * @param string $studentNumber
     * @return string
     */
    public function generateDefaultPassword(string $studentNumber): string
    {
        return $studentNumber;
    }

    /**
     * Delete a student's account (keeps student record).
     *
     * @param Student $student
     * @return bool
     */
    public function deleteAccount(Student $student): bool
    {
        $user = $student->user;
        if (!$user) {
            return false;
        }

        try {
            DB::beginTransaction();

            // Detach all roles
            $user->roles()->detach();

            // Delete user account
            $user->delete();

            DB::commit();

            Log::info("Student account deleted: student_number={$student->student_number}");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete student account: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reset a student's password.
     *
     * @param Student $student
     * @param string|null $newPassword If null, resets to student_number
     * @return bool
     */
    public function resetPassword(Student $student, ?string $newPassword = null): bool
    {
        $user = $student->user;
        if (!$user) {
            return false;
        }

        $password = $newPassword ?? $this->generateDefaultPassword($student->student_number);

        $user->update([
            'password' => $password,
            'must_change_password' => true,
        ]);

        Log::info("Student password reset: student_number={$student->student_number}");

        return true;
    }
}

