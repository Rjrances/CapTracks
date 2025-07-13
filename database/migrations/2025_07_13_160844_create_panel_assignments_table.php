<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('panel_assignments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('defense_request_id')->constrained()->onDelete('cascade');
        $table->foreignId('faculty_id')->constrained('users')->onDelete('cascade'); // assuming faculty are users
        $table->enum('role', ['chair', 'member']);
        $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
        $table->timestamp('assigned_at')->nullable();
        $table->timestamp('responded_at')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panel_assignments');
    }
};
