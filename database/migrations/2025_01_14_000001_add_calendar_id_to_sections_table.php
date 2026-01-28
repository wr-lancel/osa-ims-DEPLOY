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
        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedBigInteger('calendar_id')->nullable()->after('course_id');
            
            $table->foreign('calendar_id', 'fk_sections_calendar_id')
                ->references('calendar_id')
                ->on('academic_calendar')
                ->onDelete('set null');
            
            $table->index('calendar_id', 'idx_sections_calendar_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign('fk_sections_calendar_id');
            $table->dropIndex('idx_sections_calendar_id');
            $table->dropColumn('calendar_id');
        });
    }
};

