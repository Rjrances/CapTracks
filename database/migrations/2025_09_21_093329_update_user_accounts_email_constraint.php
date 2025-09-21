<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the unique constraint on email in user_accounts table
        Schema::table('user_accounts', function (Blueprint $table) {
            $table->dropUnique('user_accounts_email_unique');
        });
        
        // Drop the unique constraint on email in student_accounts table
        Schema::table('student_accounts', function (Blueprint $table) {
            $table->dropUnique('student_accounts_email_unique');
        });
        
        echo "✅ Removed email uniqueness constraints from account tables\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore unique constraints on email
        Schema::table('user_accounts', function (Blueprint $table) {
            $table->unique('email', 'user_accounts_email_unique');
        });
        
        Schema::table('student_accounts', function (Blueprint $table) {
            $table->unique('email', 'student_accounts_email_unique');
        });
    }
};