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
        Schema::create('candidacy_applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->unsignedBigInteger('org_id');
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('acad_id');
            $table->text('platform_statement')->nullable();
            $table->text('motivation')->nullable();
            $table->string('status')->default('submitted'); // submitted, under_review, approved, rejected, withdrawn
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();
            $table->timestamps();

            $table->foreign('org_id', 'fk_candidacy_app_org_id')
                ->references('org_id')
                ->on('student_org')
                ->onDelete('cascade');
            $table->foreign('enrollment_id', 'fk_candidacy_app_enrollment_id')
                ->references('enrollment_id')
                ->on('enrolled_students')
                ->onDelete('cascade');
            $table->foreign('position_id', 'fk_candidacy_app_position_id')
                ->references('position_id')
                ->on('org_positions')
                ->onDelete('cascade');
            $table->foreign('acad_id', 'fk_candidacy_app_acad_id')
                ->references('calendar_id')
                ->on('academic_calendar')
                ->onDelete('restrict');

            $table->unique(['org_id', 'enrollment_id', 'position_id', 'acad_id'], 'unique_candidacy_per_term');
            $table->index(['org_id', 'status'], 'idx_candidacy_app_org_status');
            $table->index('enrollment_id', 'idx_candidacy_app_enrollment');
            $table->index('acad_id', 'idx_candidacy_app_acad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidacy_applications');
    }
};
