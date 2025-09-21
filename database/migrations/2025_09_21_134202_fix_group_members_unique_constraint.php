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
        // This migration was manually executed to fix the unique constraint
        // The constraint was incorrectly set on group_id only instead of [group_id, student_id]
        // Manual SQL execution:
        // 1. ALTER TABLE group_members DROP FOREIGN KEY group_members_group_id_foreign;
        // 2. ALTER TABLE group_members DROP INDEX group_members_group_id_student_id_unique;
        // 3. ALTER TABLE group_members ADD UNIQUE KEY group_members_group_id_student_id_unique (group_id, student_id);
        // 4. ALTER TABLE group_members ADD CONSTRAINT group_members_group_id_foreign FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration was manually executed, so rollback is not needed
        // The constraint is now correctly set on [group_id, student_id]
    }
};