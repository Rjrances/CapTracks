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
        if (! Schema::hasColumn('group_members', 'student_id')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE group_members DROP FOREIGN KEY group_members_student_id_foreign');
        } catch (\Throwable $e) {
            // Foreign key may not exist yet.
        }

        try {
            DB::statement('ALTER TABLE group_members DROP INDEX group_members_group_id_student_id_unique');
        } catch (\Throwable $e) {
            // Unique index may not exist yet.
        }

        DB::statement('ALTER TABLE group_members MODIFY student_id VARCHAR(20) NOT NULL');

        try {
            DB::statement('ALTER TABLE group_members ADD UNIQUE group_members_group_id_student_id_unique (group_id, student_id)');
        } catch (\Throwable $e) {
            // Unique index may already exist.
        }

        try {
            DB::statement('ALTER TABLE group_members ADD CONSTRAINT group_members_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            // Foreign key may already exist.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('group_members', 'student_id')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE group_members DROP FOREIGN KEY group_members_student_id_foreign');
        } catch (\Throwable $e) {
            // Foreign key may not exist.
        }

        try {
            DB::statement('ALTER TABLE group_members DROP INDEX group_members_group_id_student_id_unique');
        } catch (\Throwable $e) {
            // Unique index may not exist.
        }

        DB::statement('ALTER TABLE group_members MODIFY student_id BIGINT UNSIGNED NOT NULL');

        try {
            DB::statement('ALTER TABLE group_members ADD UNIQUE group_members_group_id_student_id_unique (group_id, student_id)');
        } catch (\Throwable $e) {
            // Unique index may already exist.
        }

        try {
            DB::statement('ALTER TABLE group_members ADD CONSTRAINT group_members_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            // Foreign key may already exist.
        }
    }
};