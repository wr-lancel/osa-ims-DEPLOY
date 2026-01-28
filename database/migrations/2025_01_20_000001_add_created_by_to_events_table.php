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
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('status');
            
            $table->foreign('created_by', 'fk_events_created_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');
            
            $table->index('created_by', 'idx_events_created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign('fk_events_created_by');
            $table->dropIndex('idx_events_created_by');
            $table->dropColumn('created_by');
        });
    }
};

