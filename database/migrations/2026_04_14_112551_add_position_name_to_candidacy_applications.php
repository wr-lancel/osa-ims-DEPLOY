<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('candidacy_applications', function (Blueprint $table) {
            $table->string('position_name')->nullable()->after('position_id');
        });

        // Backfill position_name from org_positions for existing records
        DB::statement("
            UPDATE candidacy_applications ca
            JOIN org_positions op ON op.position_id = ca.position_id
            SET ca.position_name = op.position_name
        ");

        // Drop the FK and make position_id nullable
        Schema::table('candidacy_applications', function (Blueprint $table) {
            $table->dropForeign('fk_candidacy_app_position_id');
            $table->unsignedBigInteger('position_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidacy_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable(false)->change();
            $table->foreign('position_id', 'fk_candidacy_app_position_id')
                ->references('position_id')
                ->on('org_positions')
                ->onDelete('cascade');
            $table->dropColumn('position_name');
        });
    }
};
