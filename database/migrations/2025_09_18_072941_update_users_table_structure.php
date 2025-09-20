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
        Schema::table('users', function (Blueprint $table) {
            // Add faculty_id column if it doesn't exist
            if (!Schema::hasColumn('users', 'faculty_id')) {
                $table->string('faculty_id', 20)->unique()->after('id');
            }
            
            // Remove password fields
            $table->dropColumn(['password', 'must_change_password']);
            
            // Remove school_id if it exists
            if (Schema::hasColumn('users', 'school_id')) {
                $table->dropColumn('school_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add back password fields
            $table->string('password')->after('email');
            $table->boolean('must_change_password')->default(false)->after('password');
            
            // Add back school_id
            $table->string('school_id')->nullable()->after('email');
            
            // Remove faculty_id
            $table->dropColumn('faculty_id');
        });
    }
};
