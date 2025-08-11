<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class AssignUserRoles extends Command
{
    protected $signature = 'users:assign-roles';
    protected $description = 'Assign roles to existing users for testing multi-role system';

    public function handle()
    {
        $this->info('Assigning roles to existing users...');

        // Get all users
        $users = User::all();
        $roles = Role::all();

        $this->info("Found {$users->count()} users and {$roles->count()} roles");

        foreach ($users as $user) {
            $this->info("Processing user: {$user->name} ({$user->email})");
            
            // Check current roles
            $currentRoles = $user->roles->pluck('name')->toArray();
            $this->info("Current roles: " . implode(', ', $currentRoles ?: ['none']));

            // Assign roles based on email (for testing)
            $rolesToAssign = [];
            
            if (str_contains($user->email, 'coordinator')) {
                $rolesToAssign[] = 'coordinator';
            }
            if (str_contains($user->email, 'chairperson')) {
                $rolesToAssign[] = 'chairperson';
            }
            if (str_contains($user->email, 'adviser')) {
                $rolesToAssign[] = 'adviser';
            }

            // Also assign coordinator role to adviser for multi-role testing
            if (str_contains($user->email, 'adviser')) {
                $rolesToAssign[] = 'coordinator';
            }

            if (!empty($rolesToAssign)) {
                // Remove existing roles
                $user->roles()->detach();
                
                // Add new roles
                foreach ($rolesToAssign as $roleName) {
                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        $user->roles()->attach($role->id);
                        $this->info("  ✓ Assigned role: {$roleName}");
                    }
                }
            }

            $this->info('');
        }

        $this->info('Role assignment completed!');
        
        // Show final status
        $this->info('Final user roles:');
        User::with('roles')->get()->each(function($user) {
            $roles = $user->roles->pluck('name')->implode(', ');
            $this->info("  {$user->name}: {$roles}");
        });
    }
}
