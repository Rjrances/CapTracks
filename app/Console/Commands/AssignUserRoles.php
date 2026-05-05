<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\User;
class AssignUserRoles extends Command
{
    protected $signature = 'users:assign-roles';
    protected $description = 'Assign roles to existing users for testing multi-role system';
    public function handle()
    {
        $this->info('Assigning roles to existing users...');
        $users = User::all();
        $this->info("Found {$users->count()} users");
        foreach ($users as $user) {
            $this->info("Processing user: {$user->name} ({$user->email})");
            $currentRoles = $user->getRoleNames()->toArray();
            $this->info("Current roles: " . implode(', ', $currentRoles ?: ['none']));
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
            if (str_contains($user->email, 'adviser')) {
                $rolesToAssign[] = 'coordinator';
            }
            if (!empty($rolesToAssign)) {
                $rolesToAssign = array_values(array_unique($rolesToAssign));
                $user->syncRoles($rolesToAssign);
                foreach ($rolesToAssign as $roleName) {
                    $this->info("  SUCCESS: Assigned role: {$roleName}");
                }
            }
            $this->info('');
        }
        $this->info('Role assignment completed!');
        $this->info('Final user roles:');
        User::with('roles')->get()->each(function($user) {
            $roles = $user->getRoleNames()->implode(', ');
            $this->info("  {$user->name}: {$roles}");
        });
    }
}
