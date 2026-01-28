<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class RebuildOsaDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osa:rebuild-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild OSA-IMS business tables (drop and recreate)';

    /**
     * List of business tables to drop (in reverse dependency order)
     */
    protected array $businessTables = [
        'sports_borrowing',
        'risk_prediction',
        'violation_summary',
        'discipline',
        'events',
        'org_officers',
        'org_members',
        'org_advisers',
        'student_org',
        'enrolled_students',
        'academic_calendar',
        'sections',
        'courses',
        'students',
        'employees',
        'user_roles',
        'users',
        'roles',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting OSA-IMS database rebuild...');

        if (!$this->confirm('This will DROP and recreate all business tables. Continue?')) {
            $this->warn('Rebuild cancelled.');
            return Command::FAILURE;
        }

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Drop business tables
            $this->info('Dropping business tables...');
            foreach ($this->businessTables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::statement("DROP TABLE IF EXISTS `{$table}`");
                    $this->line("  ✓ Dropped: {$table}");
                } else {
                    $this->line("  - Skipped (not found): {$table}");
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('Running migrations...');
            Artisan::call('migrate', [], $this->getOutput());

            $this->info('Seeding roles...');
            Artisan::call('db:seed', ['--class' => 'RoleSeeder'], $this->getOutput());

            $this->info('✅ Database rebuild completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            // Re-enable foreign key checks in case of error
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->error('❌ Error during rebuild: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

