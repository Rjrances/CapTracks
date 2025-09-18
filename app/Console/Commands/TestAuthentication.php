<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Account;

class TestAuthentication extends Command
{
    protected $signature = 'test:auth';
    protected $description = 'Test authentication relationships';

    public function handle()
    {
        $this->info('Testing authentication relationships...');
        
        // Test faculty authentication
        $this->line('Testing faculty authentication:');
        $user = User::find(1); // Test Coordinator
        if ($user) {
            $this->line("User: {$user->name} (ID: {$user->id})");
            $this->line("Account ID: {$user->account_id}");
            
            $account = $user->account;
            if ($account) {
                $this->line("Account found: {$account->email} (Faculty ID: {$account->faculty_id})");
                $this->line("Account type: {$account->user_type}");
            } else {
                $this->error("Account relationship failed!");
            }
        }
        
        $this->line('');
        
        // Test direct account lookup
        $this->line('Testing direct account lookup:');
        $account = Account::where('faculty_id', '10001')->first();
        if ($account) {
            $this->line("Account found: {$account->email} (Faculty ID: {$account->faculty_id})");
            $this->line("Account type: {$account->user_type}");
        } else {
            $this->error("Direct account lookup failed!");
        }
        
        $this->info('Test complete!');
    }
}