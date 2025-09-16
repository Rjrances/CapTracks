<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaskSubmission;
use App\Models\ProjectSubmission;

class MigrateTaskSubmissionsToProjectSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:task-submissions-to-project-submissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing task submissions to project submissions for integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of task submissions to project submissions...');
        
        // Get all task submissions that don't have a corresponding project submission
        $taskSubmissions = TaskSubmission::with(['groupMilestoneTask.milestoneTask', 'groupMilestoneTask.groupMilestone.milestoneTemplate'])
            ->whereNotNull('file_path')
            ->get();
        
        $migratedCount = 0;
        $skippedCount = 0;
        
        foreach ($taskSubmissions as $taskSubmission) {
            // Check if a project submission already exists for this task submission
            $existingProjectSubmission = ProjectSubmission::where('student_id', $taskSubmission->student_id)
                ->where('file_path', $taskSubmission->file_path)
                ->where('type', 'other')
                ->first();
            
            if ($existingProjectSubmission) {
                $skippedCount++;
                continue;
            }
            
            // Create project submission
            ProjectSubmission::create([
                'student_id' => $taskSubmission->student_id,
                'file_path' => $taskSubmission->file_path,
                'type' => 'other', // Task submissions are categorized as 'other'
                'status' => $taskSubmission->status,
                'submitted_at' => $taskSubmission->created_at,
                'title' => $taskSubmission->groupMilestoneTask->milestoneTask->name ?? 'Task Submission',
                'objectives' => $taskSubmission->description,
                'methodology' => $taskSubmission->notes,
                'timeline' => 'Milestone: ' . ($taskSubmission->groupMilestoneTask->groupMilestone->milestoneTemplate->name ?? 'Unknown'),
                'expected_outcomes' => 'Progress: ' . ($taskSubmission->progress_percentage ?? 0) . '%',
                'teacher_comment' => $taskSubmission->adviser_feedback,
                'created_at' => $taskSubmission->created_at,
                'updated_at' => $taskSubmission->updated_at,
            ]);
            
            $migratedCount++;
        }
        
        $this->info("Migration completed!");
        $this->info("Migrated: {$migratedCount} task submissions");
        $this->info("Skipped: {$skippedCount} task submissions (already exist)");
        
        return Command::SUCCESS;
    }
}
