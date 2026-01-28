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
        Schema::create('student_educational_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->unique();
            $table->string('elementary_school')->nullable();
            $table->string('elementary_address')->nullable();
            $table->date('elementary_graduated')->nullable();
            $table->string('senior_high_school')->nullable();
            $table->string('senior_high_strand')->nullable();
            $table->string('senior_high_address')->nullable();
            $table->date('senior_high_graduated')->nullable();
            $table->text('honors_received')->nullable();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('student_id')
                ->on('students')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_educational_backgrounds');
    }
};

