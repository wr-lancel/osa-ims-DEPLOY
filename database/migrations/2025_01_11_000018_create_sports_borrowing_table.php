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
        Schema::create('sports_borrowing', function (Blueprint $table) {
            $table->id('borrowing_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->date('borrow_date');
            $table->date('return_date')->nullable();
            $table->date('expected_return_date');
            $table->string('status')->default('borrowed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id', 'fk_sports_borrowing_student_id')
                ->references('student_id')
                ->on('students')
                ->onDelete('set null');
            $table->foreign('employee_id', 'fk_sports_borrowing_employee_id')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('set null');
            
            $table->index('student_id', 'idx_sports_borrowing_student_id');
            $table->index('employee_id', 'idx_sports_borrowing_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports_borrowing');
    }
};

