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
        Schema::create('group_milestone_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_milestone_id')->constrained()->onDelete('cascade');
            $table->foreignId('milestone_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('students')->onDelete('set null');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('students')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_milestone_tasks');
    }
};
