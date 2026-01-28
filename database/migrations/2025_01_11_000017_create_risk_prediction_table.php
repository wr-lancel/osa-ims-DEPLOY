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
        Schema::create('risk_prediction', function (Blueprint $table) {
            $table->id('prediction_id');
            $table->unsignedBigInteger('student_id');
            $table->decimal('risk_score', 5, 2)->nullable();
            $table->string('risk_level')->nullable();
            $table->text('factors')->nullable();
            $table->date('prediction_date');
            $table->timestamps();

            $table->foreign('student_id', 'fk_risk_prediction_student_id')
                ->references('student_id')
                ->on('students')
                ->onDelete('cascade');
            
            $table->index('student_id', 'idx_risk_prediction_student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_prediction');
    }
};

