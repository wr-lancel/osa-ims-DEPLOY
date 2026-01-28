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
        Schema::create('enrolled_students', function (Blueprint $table) {
            $table->id('enrollment_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('section_id');
            $table->string('academic_year');
            $table->string('semester')->nullable();
            $table->string('grade')->nullable();
            $table->string('status')->default('enrolled');
            $table->date('enrollment_date');
            $table->timestamps();

            $table->foreign('student_id', 'fk_enrolled_students_student_id')
                ->references('student_id')
                ->on('students')
                ->onDelete('cascade');
            $table->foreign('section_id', 'fk_enrolled_students_section_id')
                ->references('section_id')
                ->on('sections')
                ->onDelete('cascade');
            
            $table->index('student_id', 'idx_enrolled_students_student_id');
            $table->index('section_id', 'idx_enrolled_students_section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrolled_students');
    }
};

