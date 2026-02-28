<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintPolicy
{
    use HandlesAuthorization;

    /**
     * Student can view own complaints; admin can view all.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('student') || $user->hasAnyRole(['admin', 'super_admin', 'discipline_admin']);
    }

    /**
     * Student can view only if complainant enrollment belongs to them; admin can view all.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->hasAnyRole(['admin', 'super_admin', 'discipline_admin'])) {
            return true;
        }
        if ($user->hasRole('student')) {
            $complaint->load('complainantEnrollment');
            return $complaint->complainantEnrollment
                && $complaint->complainantEnrollment->student_number === $user->student_number;
        }
        return false;
    }

    /**
     * Only students can submit complaints.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    /**
     * Only admin/discipline_admin can update (status, remarks).
     */
    public function update(User $user, Complaint $complaint): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin', 'discipline_admin']);
    }
}
