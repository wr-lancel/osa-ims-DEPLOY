<?php

namespace App\Services;

use App\Models\User;

class ModuleAuthorizationService
{
    /**
     * Module constants
     */
    public const MODULE_DASHBOARD = 'dashboard';
    public const MODULE_STUDENTS = 'students';
    public const MODULE_STAFF = 'staff';
    public const MODULE_COURSES = 'courses';
    public const MODULE_SECTIONS = 'sections';
    public const MODULE_CALENDARS = 'academic-calendars';
    public const MODULE_ROLES = 'roles';
    public const MODULE_SPORTS = 'sports';
    public const MODULE_ORGANIZATIONS = 'organizations';
    public const MODULE_DISCIPLINE = 'discipline';
    public const MODULE_GUIDANCE = 'guidance';
    public const MODULE_SETTINGS = 'settings';
    public const MODULE_PUBLICATIONS = 'publications';
    public const MODULE_GOOD_MORAL = 'good_moral';

    /**
     * Role to module mapping
     * Maps each role to the modules they can access
     */
    private const ROLE_MODULE_MAP = [
        'admin' => [
            self::MODULE_DASHBOARD,
            self::MODULE_STUDENTS,
            self::MODULE_STAFF,
            self::MODULE_COURSES,
            self::MODULE_SECTIONS,
            self::MODULE_CALENDARS,
            self::MODULE_ROLES,
            self::MODULE_SPORTS,
            self::MODULE_ORGANIZATIONS,
            self::MODULE_DISCIPLINE,
            self::MODULE_GUIDANCE,
            self::MODULE_SETTINGS,
            self::MODULE_PUBLICATIONS,
            self::MODULE_GOOD_MORAL,
        ],
        'super_admin' => [
            self::MODULE_DASHBOARD,
            self::MODULE_STUDENTS,
            self::MODULE_STAFF,
            self::MODULE_COURSES,
            self::MODULE_SECTIONS,
            self::MODULE_CALENDARS,
            self::MODULE_ROLES,
            self::MODULE_SPORTS,
            self::MODULE_ORGANIZATIONS,
            self::MODULE_DISCIPLINE,
            self::MODULE_GUIDANCE,
            self::MODULE_SETTINGS,
            self::MODULE_PUBLICATIONS,
            self::MODULE_GOOD_MORAL,
        ],
        'staff' => [
            self::MODULE_DASHBOARD,
            self::MODULE_SPORTS,
            self::MODULE_ORGANIZATIONS,
            self::MODULE_DISCIPLINE,
            self::MODULE_GUIDANCE,
            self::MODULE_GOOD_MORAL,
        ],
        'publication_admin' => [
            self::MODULE_DASHBOARD,
            self::MODULE_PUBLICATIONS,
            self::MODULE_SETTINGS,
        ],
        'sports_admin' => [
            self::MODULE_DASHBOARD,
            self::MODULE_SPORTS,
            self::MODULE_SETTINGS,
        ],
        'organization_admin' => [
            self::MODULE_DASHBOARD,
            self::MODULE_ORGANIZATIONS,
            self::MODULE_SETTINGS,
        ],
        'discipline_admin' => [
            self::MODULE_DASHBOARD,
            self::MODULE_DISCIPLINE,
            self::MODULE_SETTINGS,
        ],
        'guidance_admin' => [
            self::MODULE_DASHBOARD,
            self::MODULE_GUIDANCE,
            self::MODULE_SETTINGS,
        ],
    ];

    /**
     * Check if a user has access to a specific module
     *
     * @param User $user
     * @param string $module
     * @return bool
     */
    public function hasAccess(User $user, string $module): bool
    {
        $accessibleModules = $this->getAccessibleModules($user);
        return in_array($module, $accessibleModules, true);
    }

    /**
     * Get all modules accessible by the user
     *
     * @param User $user
     * @return array<string>
     */
    public function getAccessibleModules(User $user): array
    {
        $accessibleModules = [];

        // Load roles if not already loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Get all roles for the user
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Collect all accessible modules from all user roles
        foreach ($userRoles as $role) {
            if (isset(self::ROLE_MODULE_MAP[$role])) {
                $accessibleModules = array_merge(
                    $accessibleModules,
                    self::ROLE_MODULE_MAP[$role]
                );
            }
        }

        // Remove duplicates and return
        return array_unique($accessibleModules);
    }

    /**
     * Check if user has access to any of the given modules
     *
     * @param User $user
     * @param array<string> $modules
     * @return bool
     */
    public function hasAnyAccess(User $user, array $modules): bool
    {
        $accessibleModules = $this->getAccessibleModules($user);
        return !empty(array_intersect($modules, $accessibleModules));
    }

    /**
     * Get all available modules
     *
     * @return array<string>
     */
    public function getAllModules(): array
    {
        return [
            self::MODULE_DASHBOARD,
            self::MODULE_STUDENTS,
            self::MODULE_STAFF,
            self::MODULE_COURSES,
            self::MODULE_SECTIONS,
            self::MODULE_CALENDARS,
            self::MODULE_ROLES,
            self::MODULE_SPORTS,
            self::MODULE_ORGANIZATIONS,
            self::MODULE_DISCIPLINE,
            self::MODULE_GUIDANCE,
            self::MODULE_SETTINGS,
            self::MODULE_PUBLICATIONS,
            self::MODULE_GOOD_MORAL,
        ];
    }
}

