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
        echo "Migrating data from accounts table to separate faculty_accounts and student_accounts tables...\n";
        
        // Migrate faculty accounts
        echo "Migrating faculty accounts...\n";
        $facultyAccounts = DB::table('accounts')
            ->where('user_type', 'faculty')
            ->get(['faculty_id', 'user_id', 'email', 'password', 'created_at', 'updated_at']);
        
        foreach ($facultyAccounts as $account) {
            DB::table('faculty_accounts')->insert([
                'faculty_id' => $account->faculty_id,
                'user_id' => $account->user_id,
                'email' => $account->email,
                'password' => $account->password,
                'created_at' => $account->created_at,
                'updated_at' => $account->updated_at,
            ]);
            echo "Migrated faculty account: {$account->faculty_id}\n";
        }
        
        // Migrate student accounts
        echo "Migrating student accounts...\n";
        $studentAccounts = DB::table('accounts')
            ->where('user_type', 'student')
            ->get(['student_id', 'email', 'password', 'created_at', 'updated_at']);
        
        foreach ($studentAccounts as $account) {
            DB::table('student_accounts')->insert([
                'student_id' => $account->student_id,
                'email' => $account->email,
                'password' => $account->password,
                'created_at' => $account->created_at,
                'updated_at' => $account->updated_at,
            ]);
            echo "Migrated student account: {$account->student_id}\n";
        }
        
        echo "Data migration completed successfully!\n";
        echo "Faculty accounts: " . $facultyAccounts->count() . " records\n";
        echo "Student accounts: " . $studentAccounts->count() . " records\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "Reverting migration - clearing separate account tables...\n";
        
        DB::table('faculty_accounts')->truncate();
        DB::table('student_accounts')->truncate();
        
        echo "Separate account tables cleared!\n";
    }
};