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
        if (! Schema::hasColumn('project_submissions', 'student_id')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE project_submissions DROP FOREIGN KEY project_submissions_student_id_foreign');
        } catch (\Throwable $e) {
            // Foreign key may not exist yet.
        }

        DB::statement('ALTER TABLE project_submissions MODIFY student_id VARCHAR(20) NOT NULL');

        try {
            DB::statement('ALTER TABLE project_submissions ADD CONSTRAINT project_submissions_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            // Foreign key may already exist.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('project_submissions', 'student_id')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE project_submissions DROP FOREIGN KEY project_submissions_student_id_foreign');
        } catch (\Throwable $e) {
            // Foreign key may not exist.
        }

        DB::statement('ALTER TABLE project_submissions MODIFY student_id BIGINT UNSIGNED NOT NULL');

        try {
            DB::statement('ALTER TABLE project_submissions ADD CONSTRAINT project_submissions_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            // Foreign key may already exist.
        }
    }
};