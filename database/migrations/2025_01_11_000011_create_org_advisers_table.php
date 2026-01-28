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
        Schema::create('org_advisers', function (Blueprint $table) {
            $table->id('adviser_id');
            $table->unsignedBigInteger('org_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('org_id', 'fk_org_advisers_org_id')
                ->references('org_id')
                ->on('student_org')
                ->onDelete('cascade');
            $table->foreign('employee_id', 'fk_org_advisers_employee_id')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('cascade');
            
            $table->index('org_id', 'idx_org_advisers_org_id');
            $table->index('employee_id', 'idx_org_advisers_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_advisers');
    }
};

