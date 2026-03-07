<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * NOTE: These columns are now excluded from the initial table creation,
     * so this migration is a no-op on fresh installs.
     */
    public function up(): void
    {
        Schema::table('academic_calendar', function (Blueprint $table) {
            if (Schema::hasColumn('academic_calendar', 'event_type')) {
                $table->dropColumn('event_type');
            }
        });

        Schema::table('academic_calendar', function (Blueprint $table) {
            if (Schema::hasColumn('academic_calendar', 'title')) {
                $table->dropColumn('title');
            }
        });

        Schema::table('academic_calendar', function (Blueprint $table) {
            if (Schema::hasColumn('academic_calendar', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('academic_calendar', function (Blueprint $table) {
            if (Schema::hasColumn('academic_calendar', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: columns are managed in the initial creation migration
    }
};
