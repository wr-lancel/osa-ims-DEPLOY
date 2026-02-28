<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('guidance_appointments', function (Blueprint $table) {
            $table->text('narrative_report')->nullable()->after('admin_remarks');
            $table->string('narrative_report_file')->nullable()->after('narrative_report');
        });
    }

    public function down(): void
    {
        Schema::table('guidance_appointments', function (Blueprint $table) {
            $table->dropColumn(['narrative_report', 'narrative_report_file']);
        });
    }
};
