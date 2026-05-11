<?php

namespace App\Console\Commands;

use App\Models\StudentAccount;
use Illuminate\Console\Command;

class SetAllStudentPasswordsCommand extends Command
{
    protected $signature = 'students:set-all-passwords
                            {--password=password : Plain password stored for every student account (hashed)}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Set the same login password on all student accounts (development / recovery only).';

    public function handle(): int
    {
        $plain = (string) $this->option('password');

        if ($plain === '') {
            $this->error('Password cannot be empty.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'This will overwrite passwords for ALL student accounts. Continue?',
            false
        )) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $count = 0;

        StudentAccount::query()->orderBy('student_id')->chunkById(100, function ($accounts) use ($plain, &$count): void {
            foreach ($accounts as $account) {
                $account->password = $plain;
                $account->must_change_password = false;
                $account->save();
                $count++;
            }
        });

        $this->info("Updated {$count} student account(s) to use the given password.");

        return self::SUCCESS;
    }
}
