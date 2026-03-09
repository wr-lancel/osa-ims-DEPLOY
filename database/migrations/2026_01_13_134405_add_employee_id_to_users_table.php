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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('user_id');
            $table->foreign('employee_id', 'fk_users_employee_id')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('cascade');
            $table->index('employee_id', 'idx_users_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safely drop foreign key and index if they exist
        $fkExists = \Illuminate\Support\Facades\DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'users' 
            AND CONSTRAINT_NAME = 'fk_users_employee_id'
        ");

        if (!empty($fkExists)) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('fk_users_employee_id');
            });
        }

        $indexExists = \Illuminate\Support\Facades\DB::select("
            SELECT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'users' 
            AND INDEX_NAME = 'idx_users_employee_id'
        ");

        if (!empty($indexExists)) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_employee_id');
            });
        }

        if (Schema::hasColumn('users', 'employee_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('employee_id');
            });
        }
    }
};
