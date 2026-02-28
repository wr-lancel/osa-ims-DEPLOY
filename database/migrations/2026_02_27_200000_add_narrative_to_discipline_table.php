<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discipline', function (Blueprint $table) {
            $table->text('narrative_report')->nullable()->after('remarks');
            $table->string('narrative_report_file', 500)->nullable()->after('narrative_report');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discipline', function (Blueprint $table) {
            $table->dropColumn(['narrative_report', 'narrative_report_file']);
        });
    }
};
