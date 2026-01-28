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
        // Drop the index first if it exists
        if (Schema::hasTable('sections')) {
            try {
                Schema::table('sections', function (Blueprint $table) {
                    $table->dropIndex('idx_sections_instructor_id');
                });
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
        }
        
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'schedule')) {
                $table->dropColumn('schedule');
            }
        });
        
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'room')) {
                $table->dropColumn('room');
            }
        });
        
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'instructor_id')) {
                $table->dropColumn('instructor_id');
            }
        });
        
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'max_students')) {
                $table->dropColumn('max_students');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('schedule')->nullable()->after('section_name');
            $table->string('room')->nullable()->after('schedule');
            $table->unsignedBigInteger('instructor_id')->nullable()->after('room');
            $table->integer('max_students')->nullable()->after('instructor_id');
            
            $table->index('instructor_id', 'idx_sections_instructor_id');
        });
    }
};

