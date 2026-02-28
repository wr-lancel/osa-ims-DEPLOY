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
        Schema::create('sport_athletes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sport_id');
            $table->string('student_number');
            $table->timestamps();

            $table->foreign('sport_id')->references('sport_id')->on('sports')->onDelete('cascade');
            $table->foreign('student_number')->references('student_number')->on('students')->onDelete('cascade');
            $table->unique(['sport_id', 'student_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_athletes');
    }
};
