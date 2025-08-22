<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListAllUsers extends Command
{
    protected $signature = 'users:list-all';
    protected $description = 'List all users in the database';

    public function handle()
    {
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->error("No users found in the database!");
            return 1;
        }
        
        $this->info("All users in database:");
        $this->table(
            ['Name', 'School ID', 'Email', 'Role', 'Must Change Password'],
            $users->map(function($user) {
                return [
                    $user->name,
                    $user->school_id,
                    $user->email,
                    $user->role,
                    $user->must_change_password ? 'Yes' : 'No'
                ];
            })->toArray()
        );
        
        return 0;
    }
}
