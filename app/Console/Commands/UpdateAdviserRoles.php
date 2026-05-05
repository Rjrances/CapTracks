<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Group;
class UpdateAdviserRoles extends Command
{
    protected $signature = 'users:update-adviser-roles';
    protected $description = 'Update users who are advisers but still have teacher role';
    public function handle()
    {
        $this->info('Updating adviser roles for existing users...');
        $usersToUpdate = User::withRole('teacher')
            ->whereExists(function ($query) {
                $query->select(\DB::raw(1))
                      ->from('groups')
                      ->whereColumn('groups.faculty_id', 'users.faculty_id');
            })
            ->get();
        if ($usersToUpdate->isEmpty()) {
            $this->info('No users need role updates.');
            return;
        }
        $this->info("Found {$usersToUpdate->count()} users to update:");
        foreach ($usersToUpdate as $user) {
            $this->info("  - {$user->name} ({$user->email}) - updating from 'teacher' to 'adviser'");
            $user->assignRoles(['adviser']);
        }
        $this->info('Adviser roles updated successfully!');
        $this->info("\nSummary:");
        $this->info("- Users with 'teacher' role: " . User::withRole('teacher')->count());
        $this->info("- Users with 'adviser' role: " . User::withRole('adviser')->count());
        $this->info("- Users with 'coordinator' role: " . User::withRole('coordinator')->count());
        $this->info("- Users with 'chairperson' role: " . User::withRole('chairperson')->count());
    }
}
