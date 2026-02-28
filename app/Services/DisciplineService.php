<?php

namespace App\Services;

use App\Models\AcademicCalendar;
use App\Models\Discipline;
use App\Models\EnrolledStudent;
use App\Models\User;

class DisciplineService
{
    /**
     * Get the current (active term) enrollment for a student.
     * Returns null if no active term or no enrollment for that term.
     */
    public function currentEnrollmentForStudent(?string $studentNumber): ?EnrolledStudent
    {
        if (!$studentNumber) {
            return null;
        }

        $activeCalendar = AcademicCalendar::active()->first();
        if (!$activeCalendar) {
            return EnrolledStudent::where('student_number', $studentNumber)
                ->where('enrollment_status', 'enrolled')
                ->orderBy('enrollment_id', 'desc')
                ->first();
        }

        return EnrolledStudent::where('student_number', $studentNumber)
            ->where('acad_id', $activeCalendar->calendar_id)
            ->where('enrollment_status', 'enrolled')
            ->first();
    }

    /**
     * Resolve the user_id (recipient) for a discipline case.
     * Uses enrollment -> student -> User by student_number.
     * Returns null if student has no user account (scaffold: document for final mapping).
     */
    public function resolveUserIdForDiscipline(Discipline $discipline): ?int
    {
        $studentNumber = $discipline->student_number;
        if (!$studentNumber) {
            if ($discipline->enrollment_id && $discipline->enrollment) {
                $studentNumber = $discipline->enrollment->student_number;
            }
            if (!$studentNumber) {
                return null;
            }
        }

        $user = User::where('student_number', $studentNumber)->first();

        return $user?->user_id;
    }

    /**
     * Resolve user_id for an enrollment (student_number from enrollment -> User).
     */
    public function resolveUserIdForEnrollment(int $enrollmentId): ?int
    {
        $enrollment = EnrolledStudent::find($enrollmentId);
        if (!$enrollment) {
            return null;
        }

        $user = User::where('student_number', $enrollment->student_number)->first();

        return $user?->user_id;
    }
}
