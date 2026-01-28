<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if a foreign key exists.
     */
    protected function foreignKeyExists(string $table, string $keyName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $result = $connection->selectOne(
            "SELECT COUNT(*) as count 
             FROM information_schema.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = ? 
             AND CONSTRAINT_NAME = ?",
            [$database, $table, $keyName]
        );
        
        return $result->count > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if foreign key already exists before adding
        if (!$this->foreignKeyExists('discipline', 'fk_discipline_reported_by')) {
            Schema::table('discipline', function (Blueprint $table) {
                $table->foreign('reported_by', 'fk_discipline_reported_by')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discipline', function (Blueprint $table) {
            $table->dropForeign('fk_discipline_reported_by');
        });
    }
};
