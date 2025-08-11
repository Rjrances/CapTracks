<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['chairperson', 'coordinator', 'adviser', 'panelist']);
            $table->timestamps();
            
            // Ensure unique combination of user and role
            $table->unique(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
