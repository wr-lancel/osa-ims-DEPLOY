<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * NOTE: The `status` column is now included in the initial table creation,
     * so this migration is a no-op on fresh installs.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('academic_calendar', 'status')) {
            Schema::table('academic_calendar', function (Blueprint $table) {
                $table->enum('status', ['active', 'upcoming', 'completed'])
                    ->default('upcoming')
                    ->after('end_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('academic_calendar', 'status')) {
            Schema::table('academic_calendar', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
