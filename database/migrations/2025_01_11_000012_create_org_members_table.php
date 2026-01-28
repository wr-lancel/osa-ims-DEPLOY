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
        Schema::create('org_members', function (Blueprint $table) {
            $table->id('member_id');
            $table->unsignedBigInteger('org_id');
            $table->unsignedBigInteger('student_id');
            $table->date('join_date');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('org_id', 'fk_org_members_org_id')
                ->references('org_id')
                ->on('student_org')
                ->onDelete('cascade');
            $table->foreign('student_id', 'fk_org_members_student_id')
                ->references('student_id')
                ->on('students')
                ->onDelete('cascade');
            
            $table->index('org_id', 'idx_org_members_org_id');
            $table->index('student_id', 'idx_org_members_student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_members');
    }
};

