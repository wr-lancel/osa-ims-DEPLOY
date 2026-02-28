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
        Schema::table('discipline', function (Blueprint $table) {
            $table->unsignedBigInteger('enrollment_id')->nullable()->after('student_number');
            $table->text('sanction')->nullable()->after('description');
            $table->date('date_resolved')->nullable()->after('violation_date');
            $table->text('remarks')->nullable()->after('status');
        });

        if (Schema::hasTable('enrolled_students')) {
            Schema::table('discipline', function (Blueprint $table) {
                $table->foreign('enrollment_id', 'fk_discipline_enrollment_id')
                    ->references('enrollment_id')
                    ->on('enrolled_students')
                    ->onDelete('restrict');
                $table->index('enrollment_id', 'idx_discipline_enrollment_id');
                $table->index(['enrollment_id', 'status'], 'idx_discipline_enrollment_status');
                $table->index(['enrollment_id', 'severity'], 'idx_discipline_enrollment_severity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('enrolled_students')) {
            Schema::table('discipline', function (Blueprint $table) {
                $table->dropForeign('fk_discipline_enrollment_id');
                $table->dropIndex('idx_discipline_enrollment_id');
                $table->dropIndex('idx_discipline_enrollment_status');
                $table->dropIndex('idx_discipline_enrollment_severity');
            });
        }
        Schema::table('discipline', function (Blueprint $table) {
            $table->dropColumn(['enrollment_id', 'sanction', 'date_resolved', 'remarks']);
        });
    }
};
