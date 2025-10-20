<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use App\Models\Student;
use App\Models\Offering;
use App\Models\User;
use App\Models\DefenseRequest;
use App\Models\DefenseSchedule;

class CheckDataIntegrity extends Command
{
    protected $signature = 'db:check-integrity';
    protected $description = 'Check database integrity for common issues';

    public function handle()
    {
        $this->info('Checking Database Integrity...');
        $this->newLine();

        $issues = [];

        // Check for groups without offering
        $groupsWithoutOffering = Group::whereNull('offering_id')->count();
        if ($groupsWithoutOffering > 0) {
            $issues[] = "ERROR: {$groupsWithoutOffering} groups without offering_id";
            $this->error("Groups without offering: {$groupsWithoutOffering}");
            
            $groups = Group::whereNull('offering_id')->get(['id', 'name']);
            foreach ($groups as $group) {
                $this->line("  - Group {$group->id}: {$group->name}");
            }
        } else {
            $this->info("PASS: All groups have offering_id");
        }

        // Check for groups without members
        $groupsWithoutMembers = Group::whereDoesntHave('members')->count();
        if ($groupsWithoutMembers > 0) {
            $issues[] = "ERROR: {$groupsWithoutMembers} groups without members";
            $this->error("Groups without members: {$groupsWithoutMembers}");
            
            $groups = Group::whereDoesntHave('members')->get(['id', 'name']);
            foreach ($groups as $group) {
                $this->line("  - Group {$group->id}: {$group->name}");
            }
        } else {
            $this->info("PASS: All groups have members");
        }

        // Check for students without groups
        $studentsWithoutGroups = Student::whereDoesntHave('groups')->count();
        if ($studentsWithoutGroups > 0) {
            $issues[] = "WARNING: {$studentsWithoutGroups} students without groups";
            $this->warn("Students without groups: {$studentsWithoutGroups}");
        } else {
            $this->info("PASS: All students have groups");
        }

        // Check for students without offering enrollment
        $studentsWithoutOffering = Student::whereDoesntHave('offerings')->count();
        if ($studentsWithoutOffering > 0) {
            $issues[] = "ERROR: {$studentsWithoutOffering} students not enrolled in any offering";
            $this->error("Students without offering enrollment: {$studentsWithoutOffering}");
        } else {
            $this->info("PASS: All students are enrolled in offerings");
        }

        // Check for defense schedules without defense requests
        $schedulesWithoutRequests = DefenseSchedule::whereNotNull('defense_request_id')
            ->whereDoesntHave('defenseRequest')->count();
        if ($schedulesWithoutRequests > 0) {
            $issues[] = "ERROR: {$schedulesWithoutRequests} defense schedules with invalid defense_request_id";
            $this->error("Defense schedules with invalid defense_request_id: {$schedulesWithoutRequests}");
        } else {
            $this->info("PASS: All defense schedules have valid defense_request_id");
        }

        // Check for orphaned defense requests
        $orphanedRequests = DefenseRequest::whereDoesntHave('group')->count();
        if ($orphanedRequests > 0) {
            $issues[] = "ERROR: {$orphanedRequests} defense requests without groups";
            $this->error("Orphaned defense requests: {$orphanedRequests}");
        } else {
            $this->info("PASS: All defense requests have valid groups");
        }

        // Check for users without roles
        $usersWithoutRoles = User::whereDoesntHave('roles')->count();
        if ($usersWithoutRoles > 0) {
            $issues[] = "ERROR: {$usersWithoutRoles} users without roles";
            $this->error("Users without roles: {$usersWithoutRoles}");
        } else {
            $this->info("PASS: All users have roles");
        }

        $this->newLine();
        
        if (empty($issues)) {
            $this->info('SUCCESS: Database integrity check passed! No issues found.');
        } else {
            $this->error('ALERT: Database integrity issues found:');
            foreach ($issues as $issue) {
                $this->line($issue);
            }
            $this->newLine();
            $this->warn('These issues may cause problems during your defense. Consider fixing them.');
        }

        return 0;
    }
}