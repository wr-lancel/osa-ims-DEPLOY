<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidacy_applications', function (Blueprint $table) {
            $table->string('party_affiliation')->nullable()->after('acad_id');
        });
    }

    public function down(): void
    {
        Schema::table('candidacy_applications', function (Blueprint $table) {
            $table->dropColumn('party_affiliation');
        });
    }
};
