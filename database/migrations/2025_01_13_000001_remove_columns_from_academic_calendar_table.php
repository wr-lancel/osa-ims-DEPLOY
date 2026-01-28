<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
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
        Schema::table('academic_calendar', function (Blueprint $table) {
            $table->string('event_type')->after('end_date');
            $table->string('title')->after('event_type');
            $table->text('description')->nullable()->after('title');
            $table->boolean('is_active')->default(true)->after('description');
        });
    }
};

