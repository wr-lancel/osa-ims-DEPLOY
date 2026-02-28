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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type')->default('discipline'); // e.g. discipline
            $table->string('title');
            $table->text('message');
            $table->unsignedBigInteger('related_case_id')->nullable();
            $table->unsignedBigInteger('related_meeting_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id', 'fk_notifications_user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
            $table->foreign('related_case_id', 'fk_notifications_related_case')
                ->references('discipline_id')
                ->on('discipline')
                ->onDelete('set null');
            $table->foreign('related_meeting_id', 'fk_notifications_related_meeting')
                ->references('meeting_id')
                ->on('discipline_meetings')
                ->onDelete('set null');

            $table->index(['user_id', 'is_read', 'created_at'], 'idx_notifications_user_read_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
