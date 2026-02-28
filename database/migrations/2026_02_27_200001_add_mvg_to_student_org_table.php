<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_org', function (Blueprint $table) {
            $table->text('mission')->nullable()->after('description');
            $table->string('mission_file', 500)->nullable()->after('mission');
            $table->text('vision')->nullable()->after('mission_file');
            $table->string('vision_file', 500)->nullable()->after('vision');
            $table->text('goals')->nullable()->after('vision_file');
            $table->string('goals_file', 500)->nullable()->after('goals');
            $table->text('constitution_bylaws')->nullable()->after('goals_file');
            $table->string('constitution_bylaws_file', 500)->nullable()->after('constitution_bylaws');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_org', function (Blueprint $table) {
            $table->dropColumn([
                'mission',
                'mission_file',
                'vision',
                'vision_file',
                'goals',
                'goals_file',
                'constitution_bylaws',
                'constitution_bylaws_file',
            ]);
        });
    }
};
