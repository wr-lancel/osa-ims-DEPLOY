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
        Schema::create('guidance_case_actions', function (Blueprint $table) {
            $table->id('action_id');
            $table->unsignedBigInteger('guidance_case_id');
            $table->unsignedBigInteger('action_by_user_id');
            $table->text('note')->nullable();
            $table->enum('action_status', ['pending', 'ongoing', 'resolved', 'closed'])->nullable();
            $table->dateTime('action_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('guidance_case_id', 'fk_guid_action_case')
                ->references('guidance_case_id')
                ->on('guidance_cases')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            
            $table->foreign('action_by_user_id', 'fk_guid_action_user')
                ->references('user_id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            // Indexes
            $table->index('guidance_case_id', 'idx_guid_action_case');
            $table->index('action_by_user_id', 'idx_guid_action_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guidance_case_actions');
    }
};
