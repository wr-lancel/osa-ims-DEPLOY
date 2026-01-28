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
        Schema::create('violation_summary', function (Blueprint $table) {
            $table->id('summary_id');
            $table->unsignedBigInteger('student_id');
            $table->string('academic_year');
            $table->integer('total_violations')->default(0);
            $table->integer('minor_violations')->default(0);
            $table->integer('major_violations')->default(0);
            $table->string('status')->nullable();
            $table->timestamps();

            $table->foreign('student_id', 'fk_violation_summary_student_id')
                ->references('student_id')
                ->on('students')
                ->onDelete('cascade');
            
            $table->index('student_id', 'idx_violation_summary_student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violation_summary');
    }
};

