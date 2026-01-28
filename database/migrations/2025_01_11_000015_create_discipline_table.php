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
        Schema::create('discipline', function (Blueprint $table) {
            $table->id('discipline_id');
            $table->unsignedBigInteger('student_id');
            $table->date('violation_date');
            $table->string('violation_type');
            $table->text('description');
            $table->string('severity')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamps();

            $table->foreign('student_id', 'fk_discipline_student_id')
                ->references('student_id')
                ->on('students')
                ->onDelete('cascade');
            
            $table->index('student_id', 'idx_discipline_student_id');
            $table->index('reported_by', 'idx_discipline_reported_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipline');
    }
};

