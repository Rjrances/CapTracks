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
        echo "Creating student_accounts table...\n";
        
        Schema::create('student_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 20);
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });
        
        echo "student_accounts table created successfully!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "Dropping student_accounts table...\n";
        
        Schema::dropIfExists('student_accounts');
        
        echo "student_accounts table dropped!\n";
    }
};