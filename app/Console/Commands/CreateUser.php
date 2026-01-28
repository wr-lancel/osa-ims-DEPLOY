<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create 
                            {email : The user email address}
                            {--password= : The user password (will be prompted if not provided)}
                            {--status=active : The user status}
                            {--role=* : Assign roles to the user (e.g., --role=admin --role=staff)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user account with automatic password hashing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $status = $this->option('status') ?? 'active';
        $roles = $this->option('role');

        // Validate email format
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("  - {$error}");
            }
            return Command::FAILURE;
        }

        // Get password (prompt if not provided)
        $password = $this->option('password');
        if (!$password) {
            $password = $this->secret('Enter password (min 8 characters):');
            $passwordConfirmation = $this->secret('Confirm password:');
            
            if ($password !== $passwordConfirmation) {
                $this->error('Passwords do not match!');
                return Command::FAILURE;
            }
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');
            return Command::FAILURE;
        }

        try {
            // Create user (password will be automatically hashed by the 'hashed' cast)
            $user = User::create([
                'email' => $email,
                'password' => $password, // Automatically hashed by the 'hashed' cast
                'status' => $status,
            ]);

            $this->info("✓ User created successfully!");
            $this->table(
                ['Field', 'Value'],
                [
                    ['User ID', $user->user_id],
                    ['Email', $user->email],
                    ['Status', $user->status],
                    ['Created At', $user->created_at],
                ]
            );

            // Assign roles if provided
            if (!empty($roles)) {
                $this->info("\nAssigning roles...");
                foreach ($roles as $roleName) {
                    $role = \App\Models\Role::where('role_name', $roleName)->first();
                    if ($role) {
                        $user->roles()->attach($role->role_id);
                        $this->info("  ✓ Assigned role: {$roleName}");
                    } else {
                        $this->warn("  ✗ Role not found: {$roleName}");
                    }
                }
            }

            $this->info("\n✓ User creation complete!");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to create user: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}

