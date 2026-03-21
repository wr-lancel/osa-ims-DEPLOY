<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidacy_applications', function (Blueprint $table) {
            $table->unsignedTinyInteger('unit_load')->nullable()->after('party_affiliation');
        });
    }

    public function down(): void
    {
        Schema::table('candidacy_applications', function (Blueprint $table) {
            $table->dropColumn('unit_load');
        });
    }
};
