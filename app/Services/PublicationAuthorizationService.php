<?php

namespace App\Services;

use App\Models\OrgOfficer;
use App\Models\StudentOrganization;
use App\Models\User;

class PublicationAuthorizationService
{
    public function canManagePublications(User $user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        $roles = $user->roles->pluck('role_name')->toArray();

        if (array_intersect(['publication_admin', 'admin', 'super_admin'], $roles)) {
            return true;
        }

        return $this->isPublicationOrgOfficer($user->student_number ?? '');
    }

    public function getPublicationOrg(): ?StudentOrganization
    {
        return StudentOrganization::where('is_publication_org', true)->first();
    }

    public function isPublicationOrgOfficer(string $studentNumber): bool
    {
        if (empty($studentNumber)) {
            return false;
        }

        $pubOrg = $this->getPublicationOrg();

        if (!$pubOrg) {
            return false;
        }

        return OrgOfficer::where('org_id', $pubOrg->org_id)
            ->where('student_number', $studentNumber)
            ->where('status', 'active')
            ->exists();
    }
}
