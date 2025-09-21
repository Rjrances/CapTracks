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
        // Update semester column size in academic_terms table
        Schema::table('academic_terms', function (Blueprint $table) {
            $table->string('semester', 50)->change();
        });
        
        // Update semester column size in users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('semester', 50)->change();
        });
        
        // Update semester column size in students table
        Schema::table('students', function (Blueprint $table) {
            $table->string('semester', 50)->change();
        });
        
        echo "✅ Updated semester column size to 50 characters in all tables\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert semester column size in academic_terms table
        Schema::table('academic_terms', function (Blueprint $table) {
            $table->string('semester', 20)->change();
        });
        
        // Revert semester column size in users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('semester', 20)->change();
        });
        
        // Revert semester column size in students table
        Schema::table('students', function (Blueprint $table) {
            $table->string('semester', 20)->change();
        });
    }
};