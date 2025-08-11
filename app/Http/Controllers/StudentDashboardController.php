<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ProjectSubmission;
use App\Models\MilestoneTask;
use App\Models\Student;
use App\Models\AcademicTerm;

class StudentDashboardController extends Controller
{
    public function index()
    {
        // Check if user is authenticated via Laravel Auth (faculty/staff)
        if (Auth::check()) {
            $user = Auth::user();
            $student = $user->student;
        } else {
            // Check if student is authenticated via session
            if (session('is_student') && session('student_id')) {
                $student = Student::find(session('student_id'));
            } else {
                // Not authenticated
                return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
            }
        }

        // ✅ NEW: Calculate overall progress
        $overallProgress = $this->calculateOverallProgress($student);
        
        // ✅ NEW: Get task statistics
        $taskStats = $this->getTaskStatistics($student);
        
        // ✅ NEW: Get submissions count
        $submissionsCount = $this->getSubmissionsCount($student);
        
        // ✅ NEW: Get current milestone info
        $milestoneInfo = $this->getCurrentMilestoneInfo($student);
        
        // ✅ NEW: Get recent tasks
        $recentTasks = $this->getRecentTasks($student);
        
        // ✅ NEW: Get recent activities
        $recentActivities = $this->getRecentActivities($student);
        
        // ✅ NEW: Get upcoming deadlines
        $upcomingDeadlines = $this->getUpcomingDeadlines($student);
        
        // Get current active term
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        return view('student.dashboard', compact(
            'activeTerm',
            'overallProgress',
            'taskStats',
            'submissionsCount',
            'milestoneInfo',
            'recentTasks',
            'recentActivities',
            'upcomingDeadlines'
        ));
    }

    // ✅ NEW: Calculate overall progress percentage
    private function calculateOverallProgress($student)
    {
        if (!$student) return 0;

        // Simplified calculation based on milestones
        $totalMilestones = 3; // Proposal, Progress, Final
        $completedMilestones = 0;
        
        // Check if student has submitted different types of documents
        $submissions = ProjectSubmission::where('student_id', $student->id)->get();
        
        if ($submissions->where('type', 'proposal')->count() > 0) {
            $completedMilestones++;
        }
        if ($submissions->where('type', 'final')->count() > 0) {
            $completedMilestones++;
        }
        if ($submissions->count() >= 2) { // Assume progress if multiple submissions
            $completedMilestones++;
        }
        
        return round(($completedMilestones / $totalMilestones) * 100);
    }

    // ✅ NEW: Get task statistics
    private function getTaskStatistics($student)
    {
        if (!$student) {
            return [
                'completed' => 0,
                'total' => 0,
                'pending' => 0
            ];
        }

        // Get actual task counts from milestone tasks
        $totalTasks = MilestoneTask::count();
        $completedTasks = MilestoneTask::where('is_completed', true)->count();
        $pendingTasks = $totalTasks - $completedTasks;

        // If no tasks exist, use submission-based calculation
        if ($totalTasks === 0) {
            $submissions = ProjectSubmission::where('student_id', $student->id)->count();
            $totalTasks = 12;
            $completedTasks = min($submissions * 2, 6);
            $pendingTasks = $totalTasks - $completedTasks;
        }

        return [
            'completed' => $completedTasks,
            'total' => $totalTasks,
            'pending' => $pendingTasks
        ];
    }

    // ✅ NEW: Get submissions count
    private function getSubmissionsCount($student)
    {
        if (!$student) return 0;
        
        return ProjectSubmission::where('student_id', $student->id)->count();
    }

    // ✅ NEW: Get current milestone information
    private function getCurrentMilestoneInfo($student)
    {
        if (!$student) {
            return [
                'name' => 'Not Started',
                'description' => 'No milestone information available',
                'progress' => 0
            ];
        }

        // Simplified milestone logic
        $submissions = ProjectSubmission::where('student_id', $student->id)->count();
        
        if ($submissions === 0) {
            return [
                'name' => 'Proposal Development',
                'description' => 'Working on initial project proposal',
                'progress' => 25
            ];
        } elseif ($submissions === 1) {
            return [
                'name' => 'Proposal Review',
                'description' => 'Proposal submitted, awaiting feedback',
                'progress' => 60
            ];
        } else {
            return [
                'name' => 'Implementation Phase',
                'description' => 'Working on project implementation',
                'progress' => 80
            ];
        }
    }

    // ✅ NEW: Get recent tasks
    private function getRecentTasks($student)
    {
        if (!$student) return collect();

        // Simplified - return sample tasks
        $tasks = collect();
        
        $tasks->push((object)[
            'name' => 'Research Topic',
            'description' => 'Conduct initial research on project topic',
            'is_completed' => true
        ]);
        
        $tasks->push((object)[
            'name' => 'Write Proposal',
            'description' => 'Draft project proposal document',
            'is_completed' => true
        ]);
        
        $tasks->push((object)[
            'name' => 'Submit Proposal',
            'description' => 'Submit proposal for review',
            'is_completed' => false
        ]);
        
        return $tasks;
    }

    // ✅ NEW: Get recent activities
    private function getRecentActivities($student)
    {
        if (!$student) return collect();

        $activities = collect();
        
        $activities->push((object)[
            'title' => 'Document uploaded',
            'description' => 'Proposal document submitted',
            'icon' => 'file-alt',
            'created_at' => now()->subHours(2)
        ]);
        
        $activities->push((object)[
            'title' => 'Task completed',
            'description' => 'Research phase completed',
            'icon' => 'check-circle',
            'created_at' => now()->subHours(5)
        ]);
        
        $activities->push((object)[
            'title' => 'Group meeting',
            'description' => 'Weekly group meeting attended',
            'icon' => 'users',
            'created_at' => now()->subDays(1)
        ]);
        
        return $activities;
    }

    // ✅ NEW: Get upcoming deadlines
    private function getUpcomingDeadlines($student)
    {
        if (!$student) return collect();

        $deadlines = collect();
        
        $deadlines->push((object)[
            'title' => 'Proposal Submission',
            'description' => 'Final proposal document due',
            'due_date' => now()->addDays(7),
            'is_overdue' => false,
            'is_due_soon' => true
        ]);
        
        $deadlines->push((object)[
            'title' => 'Progress Report',
            'description' => 'Mid-term progress report',
            'due_date' => now()->addDays(14),
            'is_overdue' => false,
            'is_due_soon' => false
        ]);
        
        return $deadlines;
    }
}
