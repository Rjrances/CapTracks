<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserAccount;
use Database\Seeders\RoleSeeder;
use Database\Seeders\AcademicTermSeeder;

class DemoSetupCommand extends Command
{
    protected $signature   = 'demo:setup';
    protected $description = 'Fresh demo setup: seeds roles + academic term, creates real chairperson account.';

    public function handle(): int
    {
        $this->info('========================================');
        $this->info('  CapTrack Demo Setup');
        $this->info('========================================');
        $this->newLine();

        // Safety confirmation
        if (!$this->confirm('This will set up the demo environment. Make sure you have already run [php artisan migrate:fresh]. Continue?')) {
            $this->warn('Aborted.');
            return self::FAILURE;
        }

        // Step 1: Seed roles
        $this->info('Step 1/3 — Seeding roles...');
        $this->call('db:seed', ['--class' => RoleSeeder::class]);
        $this->line('  ✓ Roles created: chairperson, coordinator, adviser, panelist, teacher');
        $this->newLine();

        // Step 2: Seed academic terms
        $this->info('Step 2/3 — Seeding academic terms...');
        $this->call('db:seed', ['--class' => AcademicTermSeeder::class]);
        $this->line('  ✓ Active term set: 2024-2025 First Semester');
        $this->newLine();

        // Step 3: Create real chairperson
        $this->info('Step 3/3 — Creating chairperson account...');

        $chairpersonData = [
            'faculty_id' => '10001',
            'name'       => 'Mr. John Leeroy Gadiane',
            'email'      => 'john.leeroy.gadiane@university.edu',
            'department' => 'SCS',
            'role'       => 'chairperson',
            'semester'   => '2024-2025 First Semester',
        ];

        // Check if already exists (safety guard)
        $existing = User::where('faculty_id', $chairpersonData['faculty_id'])
            ->where('semester', $chairpersonData['semester'])
            ->first();

        if ($existing) {
            $this->warn("  ⚠ Chairperson (faculty_id: {$chairpersonData['faculty_id']}) already exists — skipping creation.");
        } else {
            $user = User::create($chairpersonData);
            $user->assignRoles(['chairperson']);

            // Check if UserAccount already exists
            $accountExists = UserAccount::where('faculty_id', $chairpersonData['faculty_id'])->exists();
            if (!$accountExists) {
                UserAccount::create([
                    'faculty_id' => $chairpersonData['faculty_id'],
                    'email'      => $chairpersonData['email'],
                    'password'   => Hash::make('password'),
                    'must_change_password' => false,
                ]);
            }

            $this->line("  ✓ Chairperson created: {$chairpersonData['name']}");
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('  Setup Complete!');
        $this->info('========================================');
        $this->newLine();

        $this->table(
            ['Role', 'Login ID', 'Password', 'Next Step'],
            [
                ['Chairperson', '10001', 'password', 'Log in → Import teachers.csv → Import students.csv'],
            ]
        );

        $this->newLine();
        $this->comment('After logging in as Chairperson:');
        $this->line('  1. Go to Faculty Management → Import teachers.csv');
        $this->line('     File: public/teachers.csv  |  Default password for all: password123');
        $this->line('  2. Go to Student Management  → Import students.csv');
        $this->line('     File: public/students.csv  |  Default password: first-time login flow');
        $this->line('  3. Create offerings manually in the UI');
        $this->line('  4. Enroll students into offerings');
        $this->line('  5. Proceed with demo flow (groups, Kanban, defense scheduling)');
        $this->newLine();

        $this->table(
            ['Faculty from teachers.csv', 'Login ID', 'Password'],
            [
                ['Mr. John Leeroy Gadiane (Chairperson)', '10001', 'password'],
                ['Mr. Roderick Bandalan (Teacher)',       '10002', 'password123'],
                ['Engr. Violdan Bayocot (Adviser)',       '10003', 'password123'],
                ['Mr. Temothy Homecillo (Coordinator)',   '10004', 'password123'],
                ['Engr. Vicente Patalita III (Adviser)',  '10005', 'password123'],
                ['Engr. Alyssa Tan (Teacher)',            '10006', 'password123'],
                ['Mr. James Aliazon (Adviser)',           '10007', 'password123'],
                ['Mrs. Josephine Petralba (Panelist)',    '10008', 'password123'],
                ['Engr. Carmel Tejana (Adviser)',         '10009', 'password123'],
                ['Mr. Eric Magto (Panelist)',             '10010', 'password123'],
                ['Dr. Jovelyn Cuizon (Panelist)',         '10011', 'password123'],
                ['Mr. Ahdzleebee Formentera (Coord.)',   '10012', 'password123'],
                ['Ms. Khiara Rubia (Coordinator)',        '10013', 'password123'],
            ]
        );

        return self::SUCCESS;
    }
}
