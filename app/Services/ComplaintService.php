<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Notification;
use App\Models\User;

class ComplaintService
{
    public function __construct(
        protected DisciplineService $disciplineService
    ) {}

    /**
     * Resolve user_id for the complainant (for notifications).
     */
    public function resolveUserIdForComplaint(Complaint $complaint): ?int
    {
        $complaint->load('complainantEnrollment');
        $enrollment = $complaint->complainantEnrollment;
        if (!$enrollment) {
            return null;
        }
        return $this->disciplineService->resolveUserIdForEnrollment($enrollment->enrollment_id);
    }

    /**
     * Get user_ids of discipline admins (admin, super_admin, discipline_admin).
     */
    public function getDisciplineAdminUserIds(): array
    {
        return User::whereHas('roles', function ($q) {
            $q->whereIn('role_name', ['admin', 'super_admin', 'discipline_admin']);
        })->pluck('user_id')->all();
    }

    /**
     * Notify all discipline admins of a new complaint.
     */
    public function notifyAdminsNewComplaint(Complaint $complaint): void
    {
        $adminIds = $this->getDisciplineAdminUserIds();
        $title = 'New complaint submitted';
        $message = 'A new complaint has been submitted: ' . $complaint->subject;

        foreach ($adminIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'complaint',
                'title' => $title,
                'message' => $message,
                'related_case_id' => null,
                'related_meeting_id' => null,
                'related_complaint_id' => $complaint->complaint_id,
                'is_read' => false,
            ]);
        }
    }

    /**
     * Notify the complainant student that their complaint status was updated.
     */
    public function notifyStudentStatusUpdated(Complaint $complaint, string $title, string $message): void
    {
        $userId = $this->resolveUserIdForComplaint($complaint);
        if (!$userId) {
            return;
        }
        Notification::create([
            'user_id' => $userId,
            'type' => 'complaint',
            'title' => $title,
            'message' => $message,
            'related_case_id' => null,
            'related_meeting_id' => null,
            'related_complaint_id' => $complaint->complaint_id,
            'is_read' => false,
        ]);
    }
}
