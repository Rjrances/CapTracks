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
        Schema::create('defense_panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('faculty_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['chair', 'member', 'adviser'])->default('member');
            $table->timestamps();
            
            // Prevent duplicate assignments for the same schedule and faculty
            $table->unique(['defense_schedule_id', 'faculty_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defense_panels');
    }
};
