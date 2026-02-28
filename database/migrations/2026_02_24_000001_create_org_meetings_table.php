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
        Schema::create('org_meetings', function (Blueprint $table) {
            $table->id('meeting_id');
            $table->unsignedBigInteger('org_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('venue')->nullable();
            $table->enum('target_audience', ['officers', 'members', 'all'])->default('all');
            $table->unsignedBigInteger('called_by');
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->foreign('org_id')->references('org_id')->on('student_org')->onDelete('cascade');
            $table->foreign('called_by')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_meetings');
    }
};
