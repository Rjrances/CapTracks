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
        $table->foreignId('milestone_template_id')->constrained()->onDelete('cascade');
        $table->date('requested_date');
        $table->time('requested_time');
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->text('coordinator_notes')->nullable();
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
