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
        Schema::table('sports_borrowing', function (Blueprint $table) {
            $table->text('admin_remarks')->nullable()->after('notes');
            $table->unsignedBigInteger('approved_by')->nullable()->after('admin_remarks');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');

            $table->foreign('approved_by', 'fk_sports_borrowing_approved_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');
            
            $table->foreign('rejected_by', 'fk_sports_borrowing_rejected_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');

            $table->index('approved_by', 'idx_sports_borrowing_approved_by');
            $table->index('rejected_by', 'idx_sports_borrowing_rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sports_borrowing', function (Blueprint $table) {
            $table->dropForeign('fk_sports_borrowing_approved_by');
            $table->dropForeign('fk_sports_borrowing_rejected_by');
            $table->dropIndex('idx_sports_borrowing_approved_by');
            $table->dropIndex('idx_sports_borrowing_rejected_by');
            $table->dropColumn(['admin_remarks', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at']);
        });
    }
};
