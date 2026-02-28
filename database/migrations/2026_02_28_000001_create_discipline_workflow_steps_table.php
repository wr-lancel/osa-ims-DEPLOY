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
        Schema::create('discipline_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();

            $table->index('sort_order', 'idx_dws_sort_order');
        });

        // Seed the default 7 workflow steps
        $steps = [
            ['name' => 'Violation Reported', 'description' => 'A violation has been reported and recorded in the system.', 'sort_order' => 1, 'is_terminal' => false],
            ['name' => 'Case Review', 'description' => 'The case is being reviewed by the discipline office.', 'sort_order' => 2, 'is_terminal' => false],
            ['name' => 'Student Notified', 'description' => 'The student has been notified about the violation.', 'sort_order' => 3, 'is_terminal' => false],
            ['name' => 'Investigation', 'description' => 'An investigation is being conducted.', 'sort_order' => 4, 'is_terminal' => false],
            ['name' => 'Decision Issued', 'description' => 'A decision has been issued regarding the case.', 'sort_order' => 5, 'is_terminal' => false],
            ['name' => 'Sanction Implemented', 'description' => 'The sanction has been applied.', 'sort_order' => 6, 'is_terminal' => false],
            ['name' => 'Case Closed', 'description' => 'The case has been closed.', 'sort_order' => 7, 'is_terminal' => true],
        ];

        $now = now();
        foreach ($steps as $step) {
            DB::table('discipline_workflow_steps')->insert(array_merge($step, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // Migrate existing discipline records to the new status values
        if (Schema::hasTable('discipline')) {
            DB::table('discipline')
                ->where('status', 'Pending')
                ->update(['status' => 'Violation Reported']);

            DB::table('discipline')
                ->where('status', 'Under Investigation')
                ->update(['status' => 'Investigation']);

            DB::table('discipline')
                ->where('status', 'Resolved')
                ->update(['status' => 'Case Closed']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old status values before dropping the table
        if (Schema::hasTable('discipline')) {
            DB::table('discipline')
                ->where('status', 'Violation Reported')
                ->update(['status' => 'Pending']);

            DB::table('discipline')
                ->where('status', 'Investigation')
                ->update(['status' => 'Under Investigation']);

            DB::table('discipline')
                ->where('status', 'Case Closed')
                ->update(['status' => 'Resolved']);
        }

        Schema::dropIfExists('discipline_workflow_steps');
    }
};
