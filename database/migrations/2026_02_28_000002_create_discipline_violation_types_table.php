<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discipline_violation_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('severity', ['Minor', 'Moderate', 'Major']);
            $table->text('description')->nullable();
            $table->text('default_sanction')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['name', 'severity']);
            $table->index('severity');
            $table->index('sort_order');
        });

        // Seed from existing discipline records
        $existing = DB::table('discipline')
            ->select('violation_type', 'severity')
            ->whereNotNull('severity')
            ->where('severity', '!=', '')
            ->where('violation_type', '!=', '')
            ->distinct()
            ->get();

        $now = now();
        $counters = ['Minor' => 0, 'Moderate' => 0, 'Major' => 0];

        foreach ($existing as $row) {
            $sev = $row->severity;
            if (!isset($counters[$sev]))
                continue;

            $counters[$sev]++;

            // Ignore duplicates silently
            DB::table('discipline_violation_types')->insertOrIgnore([
                'name' => ucfirst(trim($row->violation_type)),
                'severity' => $sev,
                'description' => null,
                'default_sanction' => null,
                'sort_order' => $counters[$sev],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // If no existing data, seed sensible defaults
        if ($existing->isEmpty()) {
            $defaults = [
                ['name' => 'Tardiness', 'severity' => 'Minor', 'default_sanction' => 'Verbal Warning', 'sort_order' => 1],
                ['name' => 'Improper Uniform', 'severity' => 'Minor', 'default_sanction' => 'Verbal Warning', 'sort_order' => 2],
                ['name' => 'Littering', 'severity' => 'Minor', 'default_sanction' => 'Verbal Warning', 'sort_order' => 3],
                ['name' => 'Disrespect to Faculty', 'severity' => 'Moderate', 'default_sanction' => 'Written Reprimand', 'sort_order' => 1],
                ['name' => 'Cheating', 'severity' => 'Moderate', 'default_sanction' => 'Written Reprimand + Suspension', 'sort_order' => 2],
                ['name' => 'Bullying', 'severity' => 'Major', 'default_sanction' => 'Suspension (1 week – 1 month)', 'sort_order' => 1],
                ['name' => 'Substance Use', 'severity' => 'Major', 'default_sanction' => 'Suspension + Counseling', 'sort_order' => 2],
                ['name' => 'Theft', 'severity' => 'Major', 'default_sanction' => 'Suspension + Restitution', 'sort_order' => 3],
                ['name' => 'Vandalism', 'severity' => 'Major', 'default_sanction' => 'Suspension + Restitution', 'sort_order' => 4],
            ];

            foreach ($defaults as $d) {
                DB::table('discipline_violation_types')->insert(array_merge($d, [
                    'description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipline_violation_types');
    }
};
