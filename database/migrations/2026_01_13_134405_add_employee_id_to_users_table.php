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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_users_employee_id');
            $table->dropIndex('idx_users_employee_id');
            $table->dropColumn('employee_id');
        });
    }
};
