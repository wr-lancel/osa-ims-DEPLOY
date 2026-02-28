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
        Schema::create('complaint_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('complaint_id', 'fk_complaint_history_complaint')
                ->references('complaint_id')
                ->on('complaints')
                ->onDelete('cascade');
            $table->foreign('changed_by_user_id', 'fk_complaint_history_changed_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');

            $table->index('complaint_id', 'idx_complaint_history_complaint_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_history');
    }
};
