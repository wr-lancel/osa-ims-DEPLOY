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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id', 'fk_user_roles_user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
            $table->foreign('role_id', 'fk_user_roles_role_id')
                ->references('role_id')
                ->on('roles')
                ->onDelete('cascade');
            
            $table->index('user_id', 'idx_user_roles_user_id');
            $table->index('role_id', 'idx_user_roles_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};

