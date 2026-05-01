<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('group_milestone_tasks', 'assigned_to')) {
            try {
                DB::statement('ALTER TABLE group_milestone_tasks DROP FOREIGN KEY group_milestone_tasks_assigned_to_foreign');
            } catch (\Throwable $e) {
                // Foreign key may not exist yet.
            }

            DB::statement('ALTER TABLE group_milestone_tasks MODIFY assigned_to VARCHAR(20) NULL');

            try {
                DB::statement('ALTER TABLE group_milestone_tasks ADD CONSTRAINT group_milestone_tasks_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES students(student_id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                // Foreign key may already exist.
            }
        }

        if (Schema::hasColumn('group_milestone_tasks', 'completed_by')) {
            try {
                DB::statement('ALTER TABLE group_milestone_tasks DROP FOREIGN KEY group_milestone_tasks_completed_by_foreign');
            } catch (\Throwable $e) {
                // Foreign key may not exist yet.
            }

            DB::statement('ALTER TABLE group_milestone_tasks MODIFY completed_by VARCHAR(20) NULL');

            try {
                DB::statement('ALTER TABLE group_milestone_tasks ADD CONSTRAINT group_milestone_tasks_completed_by_foreign FOREIGN KEY (completed_by) REFERENCES students(student_id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                // Foreign key may already exist.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('group_milestone_tasks', 'assigned_to')) {
            try {
                DB::statement('ALTER TABLE group_milestone_tasks DROP FOREIGN KEY group_milestone_tasks_assigned_to_foreign');
            } catch (\Throwable $e) {
                // Foreign key may not exist.
            }

            DB::statement('ALTER TABLE group_milestone_tasks MODIFY assigned_to BIGINT UNSIGNED NULL');

            try {
                DB::statement('ALTER TABLE group_milestone_tasks ADD CONSTRAINT group_milestone_tasks_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES students(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                // Foreign key may already exist.
            }
        }

        if (Schema::hasColumn('group_milestone_tasks', 'completed_by')) {
            try {
                DB::statement('ALTER TABLE group_milestone_tasks DROP FOREIGN KEY group_milestone_tasks_completed_by_foreign');
            } catch (\Throwable $e) {
                // Foreign key may not exist.
            }

            DB::statement('ALTER TABLE group_milestone_tasks MODIFY completed_by BIGINT UNSIGNED NULL');

            try {
                DB::statement('ALTER TABLE group_milestone_tasks ADD CONSTRAINT group_milestone_tasks_completed_by_foreign FOREIGN KEY (completed_by) REFERENCES students(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                // Foreign key may already exist.
            }
        }
    }
};