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
    Schema::create('defense_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('group_id')->constrained()->onDelete('cascade');
        $table->enum('defense_type', ['proposal', '60_percent', '100_percent']);
        $table->enum('status', ['pending', 'approved', 'rejected', 'scheduled'])->default('pending');
        $table->text('student_message')->nullable();
        $table->text('coordinator_notes')->nullable();
        $table->timestamp('requested_at')->nullable();
        $table->timestamp('responded_at')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defense_requests');
    }
};
