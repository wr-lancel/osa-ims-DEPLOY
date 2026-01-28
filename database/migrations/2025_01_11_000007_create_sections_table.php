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
        Schema::create('sections', function (Blueprint $table) {
            $table->id('section_id');
            $table->unsignedBigInteger('course_id');
            $table->string('section_code');
            $table->string('section_name')->nullable();
            $table->string('schedule')->nullable();
            $table->string('room')->nullable();
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->integer('max_students')->nullable();
            $table->timestamps();

            $table->foreign('course_id', 'fk_sections_course_id')
                ->references('course_id')
                ->on('courses')
                ->onDelete('cascade');
            
            $table->index('course_id', 'idx_sections_course_id');
            $table->index('instructor_id', 'idx_sections_instructor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};

