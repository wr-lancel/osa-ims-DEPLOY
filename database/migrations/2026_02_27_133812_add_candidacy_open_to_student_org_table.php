<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_org', function (Blueprint $table) {
            $table->boolean('candidacy_open')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('student_org', function (Blueprint $table) {
            $table->dropColumn('candidacy_open');
        });
    }
};
