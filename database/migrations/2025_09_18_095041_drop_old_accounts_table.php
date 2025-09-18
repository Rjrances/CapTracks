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
        echo "Dropping old accounts table...\n";
        echo "This table is no longer needed since we now use separate faculty_accounts and student_accounts tables.\n";
        
        Schema::dropIfExists('accounts');
        
        echo "Old accounts table dropped successfully!\n";
        echo "The database now uses the cleaner separate account tables architecture.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "Recreating old accounts table...\n";
        echo "Note: This will recreate an empty table structure only.\n";
        
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('faculty_id', 20)->nullable()->unique();
            $table->string('student_id', 20)->nullable()->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('user_type', ['faculty', 'student']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
        
        echo "Old accounts table recreated (empty).\n";
        echo "Data migration would need to be done separately if needed.\n";
    }
};