<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['role_name' => 'super_admin'],
            ['role_name' => 'sports_admin'],
            ['role_name' => 'organization_admin'],
            ['role_name' => 'discipline_admin'],
            ['role_name' => 'guidance_admin'],
            ['role_name' => 'student'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['role_name' => $role['role_name']],
                $role
            );
        }
    }
}

