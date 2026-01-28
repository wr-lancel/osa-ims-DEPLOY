<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HashExistingPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:hash-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hash all existing plain text passwords in the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting password hashing process...');

        $users = User::all();
        $hashedCount = 0;

        foreach ($users as $user) {
            // Check if password is already hashed (bcrypt hashes start with $2y$)
            if (!str_starts_with($user->password, '$2y$')) {
                $hashedPassword = Hash::make($user->password);
                // Use DB query builder to avoid timestamp issues
                DB::table('users')
                    ->where('user_id', $user->user_id)
                    ->update(['password' => $hashedPassword]);
                $hashedCount++;
            }
        }

        $this->info("Successfully hashed {$hashedCount} password(s).");
        $this->info('Password migration complete!');

        return Command::SUCCESS;
    }
}
