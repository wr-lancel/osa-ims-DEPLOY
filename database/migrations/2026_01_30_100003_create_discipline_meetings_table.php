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
        Schema::create('discipline_meetings', function (Blueprint $table) {
            $table->id('meeting_id');
            $table->unsignedBigInteger('case_id');
            $table->date('meeting_date');
            $table->time('meeting_time')->nullable();
            $table->string('location')->default('Discipline Office');
            $table->text('purpose_notes')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, rescheduled, completed, cancelled
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('case_id', 'fk_discipline_meetings_case_id')
                ->references('discipline_id')
                ->on('discipline')
                ->onDelete('cascade');
            $table->foreign('created_by_user_id', 'fk_discipline_meetings_created_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');

            $table->index(['case_id', 'meeting_date'], 'idx_discipline_meetings_case_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipline_meetings');
    }
};
