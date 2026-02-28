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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id('complaint_id');
            $table->unsignedBigInteger('complainant_enrolled_id');
            $table->unsignedBigInteger('respondent_enrolled_id')->nullable();
            $table->string('category');
            $table->string('subject');
            $table->text('description');
            $table->date('incident_date');
            $table->string('location')->nullable();
            $table->string('status')->default('submitted');
            $table->boolean('anonymous')->default(false);
            $table->timestamps();

            $table->foreign('complainant_enrolled_id', 'fk_complaints_complainant')
                ->references('enrollment_id')
                ->on('enrolled_students')
                ->onDelete('restrict');
            $table->foreign('respondent_enrolled_id', 'fk_complaints_respondent')
                ->references('enrollment_id')
                ->on('enrolled_students')
                ->onDelete('set null');

            $table->index(['complainant_enrolled_id', 'status'], 'idx_complaints_complainant_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
