<?php

namespace App\Policies;

use App\Models\Discipline;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisciplinePolicy
{
    use HandlesAuthorization;

    /**
     * Admin can view any violation; student can view only own (by student_number).
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole(['admin', 'super_admin', 'discipline_admin'])) {
            return true;
        }
        return $user->hasRole('student');
    }

    /**
     * Admin can view any; student can view only own.
     */
    public function view(User $user, Discipline $discipline): bool
    {
        if ($user->hasAnyRole(['admin', 'super_admin', 'discipline_admin'])) {
            return true;
        }
        if ($user->hasRole('student')) {
            return $discipline->student_number === $user->student_number;
        }
        return false;
    }

    /**
     * Only discipline admin (and admin/super_admin) can create/update/delete.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin', 'discipline_admin']);
    }

    public function update(User $user, Discipline $discipline): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin', 'discipline_admin']);
    }

    public function delete(User $user, Discipline $discipline): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin', 'discipline_admin']);
    }
}
