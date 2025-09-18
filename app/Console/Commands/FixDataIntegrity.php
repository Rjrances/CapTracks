<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use App\Models\Student;
use App\Models\Offering;
use App\Models\User;
use App\Models\Role;

class FixDataIntegrity extends Command
{
    protected $signature = 'db:fix-integrity {--force : Force fix without confirmation}';
    protected $description = 'Fix database integrity issues';

    public function handle()
    {
        $this->info('🔧 Fixing Database Integrity Issues...');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('This will modify your database. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $fixed = 0;

        // Fix 1: Assign default roles to users without roles
        $this->info('1. Fixing users without roles...');
        $usersWithoutRoles = User::whereDoesntHave('roles')->get();
        $studentRole = Role::where('name', 'student')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        
        foreach ($usersWithoutRoles as $user) {
            if ($user->email && str_contains($user->email, '@')) {
                // If user has email, likely a student
                if ($studentRole) {
                    $user->roles()->attach($studentRole->id);
                    $this->line("  ✓ Assigned student role to {$user->name}");
                    $fixed++;
                }
            } else {
                // Default to teacher role
                if ($teacherRole) {
                    $user->roles()->attach($teacherRole->id);
                    $this->line("  ✓ Assigned teacher role to {$user->name}");
                    $fixed++;
                }
            }
        }

        // Fix 2: Create a default offering if none exists
        $this->info('2. Ensuring default offering exists...');
        $defaultOffering = Offering::first();
        if (!$defaultOffering) {
            $defaultOffering = Offering::create([
                'subject_code' => 'CS 199',
                'subject_title' => 'Capstone Project I',
                'teacher_id' => User::whereHas('roles', function($q) {
                    $q->where('name', 'coordinator');
                })->first()?->id ?? User::first()?->id,
                'academic_term_id' => \App\Models\AcademicTerm::where('is_active', true)->first()?->id ?? 1
            ]);
            $this->line("  ✓ Created default offering: {$defaultOffering->subject_code}");
            $fixed++;
        }

        // Fix 3: Enroll students without offering enrollment
        $this->info('3. Enrolling students in default offering...');
        $studentsWithoutOffering = Student::whereDoesntHave('offerings')->get();
        foreach ($studentsWithoutOffering as $student) {
            $student->offerings()->attach($defaultOffering->id);
            $this->line("  ✓ Enrolled {$student->name} in {$defaultOffering->subject_code}");
            $fixed++;
        }

        // Fix 4: Assign offering_id to groups without offering
        $this->info('4. Assigning offering to groups...');
        $groupsWithoutOffering = Group::whereNull('offering_id')->get();
        foreach ($groupsWithoutOffering as $group) {
            $group->update([
                'offering_id' => $defaultOffering->id,
                'academic_term_id' => $defaultOffering->academic_term_id
            ]);
            $this->line("  ✓ Assigned offering to group: {$group->name}");
            $fixed++;
        }

        // Fix 5: Clean up empty groups (optional - ask user)
        $emptyGroups = Group::whereDoesntHave('members')->get();
        if ($emptyGroups->count() > 0) {
            $this->warn("Found {$emptyGroups->count()} empty groups:");
            foreach ($emptyGroups as $group) {
                $this->line("  - Group {$group->id}: {$group->name}");
            }
            
            if ($this->confirm('Delete empty groups?')) {
                foreach ($emptyGroups as $group) {
                    $group->delete();
                    $this->line("  ✓ Deleted empty group: {$group->name}");
                    $fixed++;
                }
            }
        }

        $this->newLine();
        $this->info("🎉 Fixed {$fixed} issues!");
        
        // Run integrity check again
        $this->info('Running integrity check...');
        $this->call('db:check-integrity');

        return 0;
    }
}