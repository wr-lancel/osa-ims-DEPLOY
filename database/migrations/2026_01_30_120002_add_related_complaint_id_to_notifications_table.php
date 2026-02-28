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
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('related_complaint_id')->nullable()->after('related_meeting_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreign('related_complaint_id', 'fk_notifications_related_complaint')
                ->references('complaint_id')
                ->on('complaints')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign('fk_notifications_related_complaint');
            $table->dropColumn('related_complaint_id');
        });
    }
};
