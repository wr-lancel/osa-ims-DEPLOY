<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Normalize all status columns to lowercase to prevent casing inconsistencies.
     *
     * Before: mixed casing ('Pending', 'pending', 'Approved', 'approved', etc.)
     * After:  all lowercase ('pending', 'approved', 'rejected', 'completed', 'cancelled', 'resolved')
     */
    public function up(): void
    {
        // Guidance appointments: had both 'approved' and 'Approved', 'pending' and 'Pending'
        DB::table('guidance_appointments')->update(['status' => DB::raw('LOWER(status)')]);

        // Discipline cases: had PascalCase ('Pending', 'Resolved', 'Under Investigation')
        DB::table('discipline')->update(['status' => DB::raw('LOWER(status)')]);

        // Complaints: normalize for consistency
        DB::table('complaints')->update(['status' => DB::raw('LOWER(status)')]);

        // Candidacy applications: had mixed 'pending'/'Pending'
        if (Schema::hasTable('candidacy_applications')) {
            DB::table('candidacy_applications')->update(['status' => DB::raw('LOWER(status)')]);
        }

        // Sports borrowings: had PascalCase 'Pending'
        if (Schema::hasTable('sports_borrowings')) {
            DB::table('sports_borrowings')->update(['status' => DB::raw('LOWER(status)')]);
        }

        // Discipline workflow steps: step names ARE the discipline status values
        if (Schema::hasTable('discipline_workflow_steps')) {
            DB::table('discipline_workflow_steps')->update(['name' => DB::raw('LOWER(name)')]);
        }

        // Discipline meetings
        if (Schema::hasTable('discipline_meetings')) {
            DB::table('discipline_meetings')->update(['status' => DB::raw('LOWER(status)')]);
        }
    }

    /**
     * This migration cannot be cleanly reversed since original casing is unknown.
     */
    public function down(): void
    {
        // No rollback — original mixed casing cannot be restored.
    }
};
