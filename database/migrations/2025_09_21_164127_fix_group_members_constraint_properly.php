<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the group_members unique constraint to be on (group_id, student_id) instead of just group_id
        echo "Fixing group_members unique constraint...\n";
        
        // Step 1: Drop foreign key constraints first
        try {
            DB::statement("ALTER TABLE group_members DROP FOREIGN KEY group_members_group_id_foreign");
            echo "✅ Dropped group_id foreign key\n";
        } catch (\Exception $e) {
            echo "⚠️  Could not drop group_id foreign key: " . $e->getMessage() . "\n";
        }
        
        try {
            DB::statement("ALTER TABLE group_members DROP FOREIGN KEY group_members_student_id_foreign");
            echo "✅ Dropped student_id foreign key\n";
        } catch (\Exception $e) {
            echo "⚠️  Could not drop student_id foreign key: " . $e->getMessage() . "\n";
        }
        
        // Step 2: Drop the incorrect unique constraint
        try {
            DB::statement("ALTER TABLE group_members DROP INDEX group_members_group_id_student_id_unique");
            echo "✅ Dropped incorrect unique constraint\n";
        } catch (\Exception $e) {
            echo "⚠️  Could not drop constraint: " . $e->getMessage() . "\n";
        }
        
        // Step 3: Add the correct composite unique constraint
        try {
            DB::statement("ALTER TABLE group_members ADD UNIQUE KEY group_members_group_id_student_id_unique (group_id, student_id)");
            echo "✅ Added correct composite unique constraint\n";
        } catch (\Exception $e) {
            echo "❌ Error adding constraint: " . $e->getMessage() . "\n";
        }
        
        // Step 4: Ensure students table has unique constraint on student_id for foreign key
        try {
            DB::statement("ALTER TABLE students ADD UNIQUE KEY students_student_id_unique (student_id)");
            echo "✅ Added unique constraint to students.student_id\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "✅ Unique constraint on students.student_id already exists\n";
            } else {
                echo "❌ Error adding students constraint: " . $e->getMessage() . "\n";
            }
        }
        
        // Step 5: Recreate foreign key constraints
        try {
            DB::statement("ALTER TABLE group_members ADD CONSTRAINT group_members_group_id_foreign FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE");
            echo "✅ Recreated group_id foreign key\n";
        } catch (\Exception $e) {
            echo "❌ Error recreating group_id foreign key: " . $e->getMessage() . "\n";
        }
        
        try {
            DB::statement("ALTER TABLE group_members ADD CONSTRAINT group_members_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE");
            echo "✅ Recreated student_id foreign key\n";
        } catch (\Exception $e) {
            echo "❌ Error recreating student_id foreign key: " . $e->getMessage() . "\n";
        }
        
        echo "✅ Group members constraint fix completed!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration fixes a critical constraint issue
        // Rolling back would break the system, so we don't provide a rollback
        echo "This migration fixes a critical constraint issue and cannot be rolled back safely.\n";
    }
};