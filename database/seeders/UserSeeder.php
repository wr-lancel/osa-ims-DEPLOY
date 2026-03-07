<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('role_name', 'super_admin')->first();
        $sportsAdminRole = Role::where('role_name', 'sports_admin')->first();
        $organizationAdminRole = Role::where('role_name', 'organization_admin')->first();
        $guidanceAdminRole = Role::where('role_name', 'guidance_admin')->first();
        $studentRole = Role::where('role_name', 'student')->first();

        // Staff users: create employee records first, then link
        $staffUsers = [
            [
                'email' => 'superadmin@osa-ims.com',
                'password' => 'superadmin123',
                'employee_number' => 'EMP-SUPERADMIN',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'department' => 'OSA',
                'position' => 'Administrator',
                'role' => $superAdminRole,
            ],
            [
                'email' => 'sportsadmin@osa-ims.com',
                'password' => 'sportsadmin123',
                'employee_number' => 'EMP-SPORTS',
                'first_name' => 'Sports',
                'last_name' => 'Admin',
                'department' => 'OSA',
                'position' => 'Sports Administrator',
                'role' => $sportsAdminRole,
            ],
            [
                'email' => 'organizationadmin@osa-ims.com',
                'password' => 'organizationadmin123',
                'employee_number' => 'EMP-ORG',
                'first_name' => 'Organization',
                'last_name' => 'Admin',
                'department' => 'OSA',
                'position' => 'Organization Administrator',
                'role' => $organizationAdminRole,
            ],
            [
                'email' => 'sports@osa-ims.com',
                'password' => 'sports123',
                'employee_number' => 'EMP-SPORTS2',
                'first_name' => 'Sports',
                'last_name' => 'Staff',
                'department' => 'OSA',
                'position' => 'Sports Staff',
                'role' => $sportsAdminRole,
            ],
            [
                'email' => 'manager@osa-ims.com',
                'password' => 'password123',
                'employee_number' => 'EMP-MANAGER',
                'first_name' => 'Manager',
                'last_name' => 'Staff',
                'department' => 'OSA',
                'position' => 'Manager',
                'role' => Role::where('role_name', 'staff')->first(),
            ],
        ];

        foreach ($staffUsers as $data) {
            $employee = Employee::firstOrCreate(
                ['employee_number' => $data['employee_number']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'department' => $data['department'],
                    'position' => $data['position'],
                ]
            );

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'password' => $data['password'],
                    'status' => 'active',
                    'employee_id' => $employee->employee_id,
                ]
            );

            if ($data['role'] && !$user->roles()->where('roles.role_id', $data['role']->role_id)->exists()) {
                $user->roles()->attach($data['role']->role_id);
            }
        }

        // Student users: create student records first, then link
        $studentUsers = [
            [
                'email' => 'student@osa-ims.com',
                'password' => 'student123',
                'student_number' => 'STU-00001',
                'first_name' => 'Sample',
                'last_name' => 'Student',
            ],
            [
                'email' => 'john.doe@student.osa-ims.com',
                'password' => 'password123',
                'student_number' => 'STU-00002',
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
            [
                'email' => 'jane.smith@student.osa-ims.com',
                'password' => 'password123',
                'student_number' => 'STU-00003',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
            ],
        ];

        foreach ($studentUsers as $data) {
            $student = Student::firstOrCreate(
                ['student_number' => $data['student_number']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'status' => 'active',
                ]
            );

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'password' => $data['password'],
                    'status' => 'active',
                    'student_number' => $student->student_number,
                ]
            );

            if ($studentRole && !$user->roles()->where('roles.role_id', $studentRole->role_id)->exists()) {
                $user->roles()->attach($studentRole->role_id);
            }
        }
    }
}

