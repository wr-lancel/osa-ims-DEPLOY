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
        Schema::create('org_positions', function (Blueprint $table) {
            $table->id('position_id');
            $table->unsignedBigInteger('org_id');
            $table->string('position_name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('org_id', 'fk_org_positions_org_id')
                ->references('org_id')
                ->on('student_org')
                ->onDelete('cascade');

            $table->unique(['org_id', 'position_name'], 'unique_org_position_name');
            $table->index('org_id', 'idx_org_positions_org_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_positions');
    }
};
