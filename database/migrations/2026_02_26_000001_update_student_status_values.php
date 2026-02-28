<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing status values
        DB::table('students')->where('status', 'active')->update(['status' => 'enrolled']);
        DB::table('students')->where('status', 'inactive')->update(['status' => 'enrolled']);

        // Also update enrollment_status for consistency
        DB::table('enrolled_students')->where('enrollment_status', 'active')->update(['enrollment_status' => 'enrolled']);
        DB::table('enrolled_students')->where('enrollment_status', 'inactive')->update(['enrollment_status' => 'enrolled']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('students')->where('status', 'enrolled')->update(['status' => 'active']);
        DB::table('enrolled_students')->where('enrollment_status', 'enrolled')->update(['enrollment_status' => 'active']);
    }
};
