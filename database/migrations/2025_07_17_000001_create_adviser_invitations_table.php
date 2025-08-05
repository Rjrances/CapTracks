<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('adviser_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('faculty_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->text('message')->nullable(); // Optional message from group
            $table->text('response_message')->nullable(); // Optional response from faculty
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            
            // Prevent multiple pending invitations from same group to same faculty
            $table->unique(['group_id', 'faculty_id', 'status'], 'unique_pending_invitation');
        });
    }

    public function down()
    {
        Schema::dropIfExists('adviser_invitations');
    }
}; 