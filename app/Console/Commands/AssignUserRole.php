<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-role {email} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a role to a user by email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return Command::FAILURE;
        }

        $role = Role::where('role_name', $roleName)->first();

        if (!$role) {
            $this->error("Role '{$roleName}' not found. Available roles: admin, staff, student, sports_admin");
            return Command::FAILURE;
        }

        // Check if user already has this role
        $existing = DB::table('user_roles')
            ->where('user_id', $user->user_id)
            ->where('role_id', $role->role_id)
            ->exists();

        if ($existing) {
            $this->warn("User already has the '{$roleName}' role.");
            return Command::SUCCESS;
        }

        // Assign the role
        DB::table('user_roles')->insert([
            'user_id' => $user->user_id,
            'role_id' => $role->role_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("Successfully assigned role '{$roleName}' to user '{$email}'.");

        return Command::SUCCESS;
    }
}
