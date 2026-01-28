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
        Schema::create('guidance_appointments', function (Blueprint $table) {
            $table->id('appointment_id');
            $table->string('student_number', 50)->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->text('concern');
            $table->enum('appointment_type', ['counseling', 'consultation', 'referral', 'other'])->default('consultation');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('student_number', 'fk_guidance_appointments_student_number')
                ->references('student_number')
                ->on('students')
                ->onDelete('set null')
                ->onUpdate('cascade');
            
            $table->foreign('employee_id', 'fk_guidance_appointments_employee_id')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('set null')
                ->onUpdate('cascade');
            
            $table->foreign('approved_by', 'fk_guidance_appointments_approved_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');
            
            $table->foreign('rejected_by', 'fk_guidance_appointments_rejected_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index('student_number', 'idx_guidance_appointments_student_number');
            $table->index('employee_id', 'idx_guidance_appointments_employee_id');
            $table->index('status', 'idx_guidance_appointments_status');
            $table->index('appointment_date', 'idx_guidance_appointments_date');
            $table->index('appointment_type', 'idx_guidance_appointments_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guidance_appointments');
    }
};
