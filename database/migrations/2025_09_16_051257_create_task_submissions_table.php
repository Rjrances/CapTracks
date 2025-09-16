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
        Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_milestone_task_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('submission_type'); // 'document', 'screenshots', 'progress_notes'
            $table->string('file_path')->nullable(); // For file uploads
            $table->text('description')->nullable(); // Progress description
            $table->text('notes')->nullable(); // Additional notes
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('adviser_feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->integer('progress_percentage')->default(0); // 0-100 for screenshots
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_submissions');
    }
};
