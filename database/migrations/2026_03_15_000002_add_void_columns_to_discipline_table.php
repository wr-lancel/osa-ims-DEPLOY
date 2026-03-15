<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discipline', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('date_resolved');
            $table->unsignedBigInteger('voided_by')->nullable()->after('voided_at');
            $table->string('void_reason')->nullable()->after('voided_by');
            $table->text('void_notes')->nullable()->after('void_reason');

            $table->foreign('voided_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('discipline', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['voided_at', 'voided_by', 'void_reason', 'void_notes']);
        });
    }
};
