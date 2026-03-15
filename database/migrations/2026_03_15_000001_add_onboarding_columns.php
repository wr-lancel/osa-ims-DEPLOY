<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('status');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->boolean('profile_completed')->default(false)->after('status');
        });

        // Existing users do not need to change password; new accounts will get true explicitly.
        // Existing students keep profile_completed = false so they complete it on next login.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('profile_completed');
        });
    }
};
