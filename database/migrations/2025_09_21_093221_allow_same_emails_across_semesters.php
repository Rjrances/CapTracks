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
        // Drop the composite unique constraints on emails
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_semester_unique');
        });
        
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_email_semester_unique');
        });
        
        // Now emails can be the same across semesters
        // We only keep the composite unique constraints for faculty_id and student_id
        echo "✅ Removed email uniqueness constraints - same emails now allowed across semesters\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore composite unique constraints on emails
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['email', 'semester'], 'users_email_semester_unique');
        });
        
        Schema::table('students', function (Blueprint $table) {
            $table->unique(['email', 'semester'], 'students_email_semester_unique');
        });
    }
};