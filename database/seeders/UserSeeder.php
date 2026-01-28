<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles for assignment
        $superAdminRole = Role::where('role_name', 'super_admin')->first();
        $sportsAdminRole = Role::where('role_name', 'sports_admin')->first();
        $organizationAdminRole = Role::where('role_name', 'organization_admin')->first();
        $guidanceAdminRole = Role::where('role_name', 'guidance_admin')->first();
        $studentRole = Role::where('role_name', 'student')->first();

        // Super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@osa-ims.com'],
            [
                'password' => 'superadmin123', // Automatically hashed by the 'hashed' cast
                'status' => 'active',
            ]
        );
            if ($superAdminRole && !$superAdmin->roles()->where('roles.role_id', $superAdminRole->role_id)->exists()) {
            $superAdmin->roles()->attach($superAdminRole->role_id);
        }

        // Sports admin user
        $sportsAdmin = User::firstOrCreate(
            ['email' => 'sportsadmin@osa-ims.com'],
            [
                'password' => 'sportsadmin123', // Automatically hashed by the 'hashed' cast
                'status' => 'active',
            ]
        );
        if ($sportsAdminRole && !$sportsAdmin->roles()->where('roles.role_id', $sportsAdminRole->role_id)->exists()) {
            $sportsAdmin->roles()->attach($sportsAdminRole->role_id);
        }

        // Organization admin user
        $organizationAdmin = User::firstOrCreate(
            ['email' => 'organizationadmin@osa-ims.com'],
            [
                'password' => 'organizationadmin123', // Automatically hashed by the 'hashed' cast
                'status' => 'active',
            ]
        );
        if ($organizationAdminRole && !$organizationAdmin->roles()->where('roles.role_id', $organizationAdminRole->role_id)->exists()) {
            $organizationAdmin->roles()->attach($organizationAdminRole->role_id);
        }

        // Student user
        $student = User::firstOrCreate(
            ['email' => 'student@osa-ims.com'],
            [
                'password' => 'student123', // Automatically hashed by the 'hashed' cast
                'status' => 'active',
            ]
        );
        if ($studentRole && !$student->roles()->where('roles.role_id', $studentRole->role_id)->exists()) {
            $student->roles()->attach($studentRole->role_id);
        }

        // Sports admin user
        $sportsAdmin = User::firstOrCreate(
            ['email' => 'sports@osa-ims.com'],
            [
                'password' => 'sports123', // Automatically hashed by the 'hashed' cast
                'status' => 'active',
            ]
        );
        if ($sportsAdminRole && !$sportsAdmin->roles()->where('roles.role_id', $sportsAdminRole->role_id)->exists()) {
            $sportsAdmin->roles()->attach($sportsAdminRole->role_id);
        }

        // Optional: Create additional sample users
        $sampleUsers = [
            [
                'email' => 'john.doe@student.osa-ims.com',
                'password' => 'password123',
                'status' => 'active',
                'role' => 'student',
            ],
            [
                'email' => 'jane.smith@student.osa-ims.com',
                'password' => 'password123',
                'status' => 'active',
                'role' => 'student',
            ],
            [
                'email' => 'manager@osa-ims.com',
                'password' => 'password123',
                'status' => 'active',
                'role' => 'staff',
            ],
        ];

        foreach ($sampleUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'password' => $userData['password'], // Automatically hashed
                    'status' => $userData['status'],
                ]
            );

            // Assign role
            if (isset($userData['role'])) {
                $role = Role::where('role_name', $userData['role'])->first();
                if ($role && !$user->roles()->where('roles.role_id', $role->role_id)->exists()) {
                    $user->roles()->attach($role->role_id);
                }
            }
        }
    }
}

