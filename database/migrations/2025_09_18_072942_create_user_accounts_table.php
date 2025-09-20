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
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('faculty_id', 20);
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('faculty_id')->references('faculty_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};