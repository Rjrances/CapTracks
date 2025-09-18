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
        echo "Creating faculty_accounts table...\n";
        
        Schema::create('faculty_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('faculty_id', 20)->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        echo "faculty_accounts table created successfully!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "Dropping faculty_accounts table...\n";
        
        Schema::dropIfExists('faculty_accounts');
        
        echo "faculty_accounts table dropped!\n";
    }
};