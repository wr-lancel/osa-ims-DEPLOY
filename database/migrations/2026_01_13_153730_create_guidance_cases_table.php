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
        Schema::create('guidance_cases', function (Blueprint $table) {
            $table->id('guidance_case_id');
            $table->unsignedBigInteger('enrollment_id');
            $table->string('case_no', 50)->unique();
            $table->string('case_type', 100); // counseling, consultation, referral
            $table->text('concern')->nullable();
            $table->enum('status', ['pending', 'ongoing', 'resolved', 'closed'])->default('pending');
            $table->unsignedBigInteger('assigned_staff_id')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('enrollment_id', 'fk_guid_case_enrollment')
                ->references('enrollment_id')
                ->on('enrolled_students')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            
            $table->foreign('assigned_staff_id', 'fk_guid_case_staff')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('set null')
                ->onUpdate('cascade');

            // Indexes
            $table->index('enrollment_id', 'idx_guid_case_enrollment');
            $table->index('assigned_staff_id', 'idx_guid_case_staff');
            $table->index('status', 'idx_guid_case_status');
            $table->index('case_type', 'idx_guid_case_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guidance_cases');
    }
};
