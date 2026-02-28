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
        Schema::create('discipline_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('case_id', 'fk_discipline_history_case_id')
                ->references('discipline_id')
                ->on('discipline')
                ->onDelete('cascade');
            $table->foreign('changed_by_user_id', 'fk_discipline_history_changed_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');

            $table->index('case_id', 'idx_discipline_history_case_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipline_history');
    }
};
