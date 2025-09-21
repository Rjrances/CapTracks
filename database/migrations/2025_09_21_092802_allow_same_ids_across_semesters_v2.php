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
        // Step 1: Drop ALL foreign key constraints that reference the fields we want to modify
        $this->dropAllForeignKeys();
        
        // Step 2: Drop existing unique constraints
        $this->dropUniqueConstraints();
        
        // Step 3: Add composite unique constraints
        $this->addCompositeUniqueConstraints();
        
        // Step 4: Recreate foreign key constraints
        $this->recreateAllForeignKeys();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints
        $this->dropAllForeignKeys();
        
        // Drop composite unique constraints
        $this->dropCompositeUniqueConstraints();
        
        // Restore original unique constraints
        $this->restoreOriginalUniqueConstraints();
        
        // Recreate foreign key constraints
        $this->recreateAllForeignKeys();
    }
    
    private function dropAllForeignKeys()
    {
        $foreignKeys = [
            // Users table foreign keys
            'user_accounts_faculty_id_foreign',
            'offerings_faculty_id_foreign', 
            'groups_faculty_id_foreign',
            'adviser_invitations_faculty_id_foreign',
            'defense_panels_faculty_id_foreign',
            'faculty_roles_user_id_foreign',
            'notifications_user_id_foreign',
            'panel_assignments_faculty_id_foreign',
            'task_submissions_reviewed_by_foreign',
            'user_roles_user_id_foreign',
            
            // Students table foreign keys
            'enrollments_student_id_foreign',
            'group_milestone_tasks_assigned_to_foreign',
            'group_milestone_tasks_completed_by_foreign',
            'project_submissions_student_id_foreign',
            'student_accounts_student_id_foreign',
            'task_submissions_student_id_foreign',
            'offering_student_student_id_foreign',
            'group_members_student_id_foreign',
            'students_offer_code_foreign',
            
            // Offerings table foreign keys
            'groups_offering_id_foreign',
            'offering_student_offering_id_foreign'
        ];
        
        foreach ($foreignKeys as $fk) {
            try {
                $table = $this->getTableNameFromForeignKey($fk);
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
                echo "Dropped foreign key: {$fk}\n";
            } catch (\Exception $e) {
                echo "Could not drop {$fk}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    private function getTableNameFromForeignKey($fk)
    {
        $tableMap = [
            'user_accounts_faculty_id_foreign' => 'user_accounts',
            'offerings_faculty_id_foreign' => 'offerings',
            'groups_faculty_id_foreign' => 'groups',
            'adviser_invitations_faculty_id_foreign' => 'adviser_invitations',
            'defense_panels_faculty_id_foreign' => 'defense_panels',
            'faculty_roles_user_id_foreign' => 'faculty_roles',
            'notifications_user_id_foreign' => 'notifications',
            'panel_assignments_faculty_id_foreign' => 'panel_assignments',
            'task_submissions_reviewed_by_foreign' => 'task_submissions',
            'user_roles_user_id_foreign' => 'user_roles',
            'enrollments_student_id_foreign' => 'enrollments',
            'group_milestone_tasks_assigned_to_foreign' => 'group_milestone_tasks',
            'group_milestone_tasks_completed_by_foreign' => 'group_milestone_tasks',
            'project_submissions_student_id_foreign' => 'project_submissions',
            'student_accounts_student_id_foreign' => 'student_accounts',
            'task_submissions_student_id_foreign' => 'task_submissions',
            'offering_student_student_id_foreign' => 'offering_student',
            'group_members_student_id_foreign' => 'group_members',
            'students_offer_code_foreign' => 'students',
            'groups_offering_id_foreign' => 'groups',
            'offering_student_offering_id_foreign' => 'offering_student'
        ];
        
        return $tableMap[$fk] ?? 'unknown';
    }
    
    private function dropUniqueConstraints()
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_faculty_id_unique');
                $table->dropUnique('users_email_unique');
            });
            echo "Dropped users unique constraints\n";
        } catch (\Exception $e) {
            echo "Could not drop users constraints: " . $e->getMessage() . "\n";
        }
        
        try {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_student_id_unique');
                $table->dropUnique('students_email_unique');
            });
            echo "Dropped students unique constraints\n";
        } catch (\Exception $e) {
            echo "Could not drop students constraints: " . $e->getMessage() . "\n";
        }
        
        try {
            Schema::table('offerings', function (Blueprint $table) {
                $table->dropUnique('offerings_offer_code_unique');
            });
            echo "Dropped offerings unique constraints\n";
        } catch (\Exception $e) {
            echo "Could not drop offerings constraints: " . $e->getMessage() . "\n";
        }
    }
    
    private function addCompositeUniqueConstraints()
    {
        // Add composite unique constraints for users
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['faculty_id', 'semester'], 'users_faculty_id_semester_unique');
            $table->unique(['email', 'semester'], 'users_email_semester_unique');
        });
        echo "Added users composite unique constraints\n";
        
        // Add composite unique constraints for students
        Schema::table('students', function (Blueprint $table) {
            $table->unique(['student_id', 'semester'], 'students_student_id_semester_unique');
            $table->unique(['email', 'semester'], 'students_email_semester_unique');
        });
        echo "Added students composite unique constraints\n";
        
        // Add composite unique constraints for offerings
        Schema::table('offerings', function (Blueprint $table) {
            $table->unique(['offer_code', 'academic_term_id'], 'offerings_offer_code_term_unique');
        });
        echo "Added offerings composite unique constraints\n";
    }
    
    private function dropCompositeUniqueConstraints()
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_faculty_id_semester_unique');
                $table->dropUnique('users_email_semester_unique');
            });
        } catch (\Exception $e) {
            // Ignore
        }
        
        try {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_student_id_semester_unique');
                $table->dropUnique('students_email_semester_unique');
            });
        } catch (\Exception $e) {
            // Ignore
        }
        
        try {
            Schema::table('offerings', function (Blueprint $table) {
                $table->dropUnique('offerings_offer_code_term_unique');
            });
        } catch (\Exception $e) {
            // Ignore
        }
    }
    
    private function restoreOriginalUniqueConstraints()
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('faculty_id', 'users_faculty_id_unique');
                $table->unique('email', 'users_email_unique');
            });
        } catch (\Exception $e) {
            // Ignore
        }
        
        try {
            Schema::table('students', function (Blueprint $table) {
                $table->unique('student_id', 'students_student_id_unique');
                $table->unique('email', 'students_email_unique');
            });
        } catch (\Exception $e) {
            // Ignore
        }
        
        try {
            Schema::table('offerings', function (Blueprint $table) {
                $table->unique('offer_code', 'offerings_offer_code_unique');
            });
        } catch (\Exception $e) {
            // Ignore
        }
    }
    
    private function recreateAllForeignKeys()
    {
        // Recreate foreign key constraints
        $foreignKeys = [
            // Users table foreign keys
            ['table' => 'user_accounts', 'column' => 'faculty_id', 'ref_table' => 'users', 'ref_column' => 'faculty_id', 'name' => 'user_accounts_faculty_id_foreign'],
            ['table' => 'offerings', 'column' => 'faculty_id', 'ref_table' => 'users', 'ref_column' => 'faculty_id', 'name' => 'offerings_faculty_id_foreign'],
            ['table' => 'groups', 'column' => 'faculty_id', 'ref_table' => 'users', 'ref_column' => 'faculty_id', 'name' => 'groups_faculty_id_foreign'],
            ['table' => 'adviser_invitations', 'column' => 'faculty_id', 'ref_table' => 'users', 'ref_column' => 'id', 'name' => 'adviser_invitations_faculty_id_foreign'],
            ['table' => 'defense_panels', 'column' => 'faculty_id', 'ref_table' => 'users', 'ref_column' => 'id', 'name' => 'defense_panels_faculty_id_foreign'],
            ['table' => 'faculty_roles', 'column' => 'user_id', 'ref_table' => 'users', 'ref_column' => 'id', 'name' => 'faculty_roles_user_id_foreign'],
            ['table' => 'notifications', 'column' => 'user_id', 'ref_table' => 'users', 'ref_column' => 'id', 'name' => 'notifications_user_id_foreign'],
            ['table' => 'panel_assignments', 'column' => 'faculty_id', 'ref_table' => 'users', 'ref_column' => 'id', 'name' => 'panel_assignments_faculty_id_foreign'],
            ['table' => 'task_submissions', 'column' => 'reviewed_by', 'ref_table' => 'users', 'ref_column' => 'id', 'name' => 'task_submissions_reviewed_by_foreign'],
            ['table' => 'user_roles', 'column' => 'user_id', 'ref_table' => 'users', 'ref_column' => 'id', 'name' => 'user_roles_user_id_foreign'],
            
            // Students table foreign keys
            ['table' => 'enrollments', 'column' => 'student_id', 'ref_table' => 'students', 'ref_column' => 'student_id', 'name' => 'enrollments_student_id_foreign'],
            ['table' => 'group_milestone_tasks', 'column' => 'assigned_to', 'ref_table' => 'students', 'ref_column' => 'student_id', 'name' => 'group_milestone_tasks_assigned_to_foreign'],
            ['table' => 'group_milestone_tasks', 'column' => 'completed_by', 'ref_table' => 'students', 'ref_column' => 'student_id', 'name' => 'group_milestone_tasks_completed_by_foreign'],
            ['table' => 'project_submissions', 'column' => 'student_id', 'ref_table' => 'students', 'ref_column' => 'student_id', 'name' => 'project_submissions_student_id_foreign'],
            ['table' => 'student_accounts', 'column' => 'student_id', 'ref_table' => 'students', 'ref_column' => 'student_id', 'name' => 'student_accounts_student_id_foreign'],
            ['table' => 'task_submissions', 'column' => 'student_id', 'ref_table' => 'students', 'ref_column' => 'student_id', 'name' => 'task_submissions_student_id_foreign'],
            ['table' => 'offering_student', 'column' => 'student_id', 'ref_table' => 'students', 'ref_column' => 'student_id', 'name' => 'offering_student_student_id_foreign'],
            ['table' => 'group_members', 'column' => 'student_id', 'ref_table' => 'students', 'ref_column' => 'student_id', 'name' => 'group_members_student_id_foreign'],
            ['table' => 'students', 'column' => 'offer_code', 'ref_table' => 'offerings', 'ref_column' => 'offer_code', 'name' => 'students_offer_code_foreign'],
            
            // Offerings table foreign keys
            ['table' => 'groups', 'column' => 'offering_id', 'ref_table' => 'offerings', 'ref_column' => 'id', 'name' => 'groups_offering_id_foreign'],
            ['table' => 'offering_student', 'column' => 'offering_id', 'ref_table' => 'offerings', 'ref_column' => 'id', 'name' => 'offering_student_offering_id_foreign']
        ];
        
        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE `{$fk['table']}` ADD CONSTRAINT `{$fk['name']}` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['ref_table']}`(`{$fk['ref_column']}`)");
                echo "Recreated foreign key: {$fk['name']}\n";
            } catch (\Exception $e) {
                echo "Could not recreate {$fk['name']}: " . $e->getMessage() . "\n";
            }
        }
    }
};