<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\TaskSubmission;
use App\Models\ProjectSubmission;
class MigrateTaskSubmissionsToProjectSubmissions extends Command
{
    protected $signature = 'migrate:task-submissions-to-project-submissions';
    protected $description = 'Migrate existing task submissions to project submissions for integration';
    public function handle()
    {
        $this->info('Starting migration of task submissions to project submissions...');
        $taskSubmissions = TaskSubmission::with(['groupMilestoneTask.milestoneTask', 'groupMilestoneTask.groupMilestone.milestoneTemplate'])
            ->whereNotNull('file_path')
            ->get();
        $migratedCount = 0;
        $skippedCount = 0;
        foreach ($taskSubmissions as $taskSubmission) {
            $existingProjectSubmission = ProjectSubmission::where('student_id', $taskSubmission->student_id)
                ->where('file_path', $taskSubmission->file_path)
                ->where('type', 'other')
                ->first();
            if ($existingProjectSubmission) {
                $skippedCount++;
                continue;
            }
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
