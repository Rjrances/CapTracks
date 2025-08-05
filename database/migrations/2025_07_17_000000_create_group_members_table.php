<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['leader', 'member'])->default('member');
            $table->timestamps();
            
            $table->unique(['group_id', 'student_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_members');
    }
}; 