<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ProjectSubmission;
use App\Models\MilestoneTask;
use App\Models\Student;
use App\Models\AcademicTerm;
use App\Models\Group;
use App\Models\DefenseRequest;
use App\Models\DefenseSchedule;
use App\Models\Notification;

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

        // ✅ NEW: Get student's group information
        $group = $student->groups()->with(['adviser', 'adviserInvitations.faculty', 'defenseRequests', 'defenseSchedules'])->first();
        
        // ✅ NEW: Calculate overall progress
        $overallProgress = $this->calculateOverallProgress($student, $group);
        
        // ✅ NEW: Get task statistics
        $taskStats = $this->getTaskStatistics($student, $group);
        
        // ✅ NEW: Get submissions count
        $submissionsCount = $this->getSubmissionsCount($student);
        
        // ✅ NEW: Get current milestone info
        $milestoneInfo = $this->getCurrentMilestoneInfo($student, $group);
        
        // ✅ NEW: Get recent tasks
        $recentTasks = $this->getRecentTasks($student, $group);
        
        // ✅ NEW: Get recent activities
        $recentActivities = $this->getRecentActivities($student);
        
        // ✅ NEW: Get upcoming deadlines
        $upcomingDeadlines = $this->getUpcomingDeadlines($student, $group);
        
        // ✅ NEW: Get adviser information
        $adviserInfo = $this->getAdviserInfo($group);
        
        // ✅ NEW: Get defense schedule information
        $defenseInfo = $this->getDefenseInfo($group);
        
        // ✅ NEW: Get notifications
        $notifications = $this->getNotifications($student);
        
        // ✅ NEW: Get existing proposal for 60% defense readiness
        $existingProposal = $this->getExistingProposal($student);
        
        // Get current active term
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        return view('student.dashboard', compact(
            'activeTerm',
            'student',
            'group',
            'overallProgress',
            'taskStats',
            'submissionsCount',
            'milestoneInfo',
            'recentTasks',
            'recentActivities',
            'upcomingDeadlines',
            'adviserInfo',
            'defenseInfo',
            'notifications',
            'existingProposal'
        ));
    }

    // ✅ NEW: Calculate overall progress percentage
    private function calculateOverallProgress($student, $group = null)
    {
        if (!$student) return 0;

        // If student has a group, use group milestone progress
        if ($group && $group->groupMilestones->count() > 0) {
            $totalProgress = $group->groupMilestones->sum('progress_percentage');
            return round($totalProgress / $group->groupMilestones->count());
        }

        // Fallback to submission-based calculation
        $totalMilestones = 3; // Proposal, Progress, Final
        $completedMilestones = 0;
        
        $submissions = ProjectSubmission::where('student_id', $student->id)->get();
        
        if ($submissions->where('type', 'proposal')->count() > 0) {
            $completedMilestones++;
        }
        if ($submissions->where('type', 'final')->count() > 0) {
            $completedMilestones++;
        }
        if ($submissions->count() >= 2) {
            $completedMilestones++;
        }
        
        return round(($completedMilestones / $totalMilestones) * 100);
    }

    // ✅ NEW: Get task statistics
    private function getTaskStatistics($student, $group = null)
    {
        if (!$student) {
            return [
                'completed' => 0,
                'total' => 0,
                'pending' => 0,
                'doing' => 0
            ];
        }

        // If student has a group, use group milestone tasks
        if ($group) {
            $tasks = $group->groupMilestones->flatMap->groupTasks;
            $totalTasks = $tasks->count();
            $completedTasks = $tasks->where('status', 'done')->count();
            $doingTasks = $tasks->where('status', 'doing')->count();
            $pendingTasks = $tasks->where('status', 'pending')->count();

            return [
                'completed' => $completedTasks,
                'total' => $totalTasks,
                'pending' => $pendingTasks,
                'doing' => $doingTasks
            ];
        }

        // Fallback to general task counts
        $totalTasks = MilestoneTask::count();
        $completedTasks = MilestoneTask::where('is_completed', true)->count();
        $pendingTasks = $totalTasks - $completedTasks;

        if ($totalTasks === 0) {
            $submissions = ProjectSubmission::where('student_id', $student->id)->count();
            $totalTasks = 12;
            $completedTasks = min($submissions * 2, 6);
            $pendingTasks = $totalTasks - $completedTasks;
        }

        return [
            'completed' => $completedTasks,
            'total' => $totalTasks,
            'pending' => $pendingTasks,
            'doing' => 0
        ];
    }

    // ✅ NEW: Get submissions count
    private function getSubmissionsCount($student)
    {
        if (!$student) return 0;
        
        return ProjectSubmission::where('student_id', $student->id)->count();
    }

    // ✅ NEW: Get current milestone information
    private function getCurrentMilestoneInfo($student, $group = null)
    {
        if (!$student) {
            return [
                'name' => 'Not Started',
                'description' => 'No milestone information available',
                'progress' => 0
            ];
        }

        // If student has a group, use group milestones
        if ($group && $group->groupMilestones->count() > 0) {
            $currentMilestone = $group->groupMilestones->where('status', '!=', 'completed')->first();
            if (!$currentMilestone) {
                $currentMilestone = $group->groupMilestones->last();
            }
            
            if ($currentMilestone) {
                return [
                    'name' => $currentMilestone->milestoneTemplate->name,
                    'description' => $currentMilestone->milestoneTemplate->description,
                    'progress' => $currentMilestone->progress_percentage,
                    'status' => $currentMilestone->status
                ];
            }
        }

        // Fallback to submission-based logic
        $submissions = ProjectSubmission::where('student_id', $student->id)->count();
        
        if ($submissions === 0) {
            return [
                'name' => 'Proposal Development',
                'description' => 'Working on initial project proposal',
                'progress' => 25,
                'status' => 'not_started'
            ];
        } elseif ($submissions === 1) {
            return [
                'name' => 'Proposal Review',
                'description' => 'Proposal submitted, awaiting feedback',
                'progress' => 60,
                'status' => 'in_progress'
            ];
        } else {
            return [
                'name' => 'Implementation Phase',
                'description' => 'Working on project implementation',
                'progress' => 80,
                'status' => 'in_progress'
            ];
        }
    }

    // ✅ NEW: Get recent tasks
    private function getRecentTasks($student, $group = null)
    {
        if (!$student) return collect();

        // If student has a group, use actual group milestone tasks
        if ($group) {
            $tasks = $group->groupMilestones->flatMap->groupTasks->take(5);
            return $tasks->map(function($task) {
                return (object)[
                    'name' => $task->milestoneTask->name,
                    'description' => $task->milestoneTask->description,
                    'status' => $task->status,
                    'is_completed' => $task->status === 'done',
                    'assigned_to' => $task->assignedStudent ? $task->assignedStudent->name : null
                ];
            });
        }

        // Fallback to sample tasks
        $tasks = collect();
        
        $tasks->push((object)[
            'name' => 'Research Topic',
            'description' => 'Conduct initial research on project topic',
            'status' => 'done',
            'is_completed' => true,
            'assigned_to' => null
        ]);
        
        $tasks->push((object)[
            'name' => 'Write Proposal',
            'description' => 'Draft project proposal document',
            'status' => 'done',
            'is_completed' => true,
            'assigned_to' => null
        ]);
        
        $tasks->push((object)[
            'name' => 'Submit Proposal',
            'description' => 'Submit proposal for review',
            'status' => 'pending',
            'is_completed' => false,
            'assigned_to' => null
        ]);
        
        return $tasks;
    }

    // ✅ NEW: Get recent activities
    private function getRecentActivities($student)
    {
        if (!$student) return collect();

        $activities = collect();
        
        // Get recent submissions
        $recentSubmissions = ProjectSubmission::where('student_id', $student->id)
            ->latest()
            ->take(3)
            ->get();
            
        foreach ($recentSubmissions as $submission) {
            $activities->push((object)[
                'title' => 'Document uploaded',
                'description' => ucfirst($submission->type) . ' document submitted',
                'icon' => 'file-alt',
                'created_at' => $submission->created_at,
                'type' => 'submission'
            ]);
        }
        
        // Get recent task completions
        $group = $student->groups()->first();
        if ($group) {
            $recentCompletedTasks = $group->groupMilestones->flatMap->groupTasks
                ->where('status', 'done')
                ->where('completed_at', '>=', now()->subDays(7))
                ->take(2);
                
            foreach ($recentCompletedTasks as $task) {
                $activities->push((object)[
                    'title' => 'Task completed',
                    'description' => $task->milestoneTask->name . ' completed',
                    'icon' => 'check-circle',
                    'created_at' => $task->completed_at,
                    'type' => 'task'
                ]);
            }
        }
        
        // Sort by creation date and take top 5
        return $activities->sortByDesc('created_at')->take(5);
    }

    // ✅ NEW: Get upcoming deadlines
    private function getUpcomingDeadlines($student, $group = null)
    {
        if (!$student) return collect();

        $deadlines = collect();
        
        // Get group milestone deadlines
        if ($group) {
            $milestoneDeadlines = $group->groupMilestones->map(function($milestone) {
                return (object)[
                    'title' => $milestone->milestoneTemplate->name,
                    'description' => 'Milestone deadline',
                    'due_date' => $milestone->target_date,
                    'is_overdue' => $milestone->target_date && $milestone->target_date->isPast(),
                    'is_due_soon' => $milestone->target_date && $milestone->target_date->diffInDays(now()) <= 7,
                    'type' => 'milestone'
                ];
            })->filter(function($deadline) {
                return $deadline->due_date;
            });
            
            $deadlines = $deadlines->merge($milestoneDeadlines);
        }
        
        // Get task deadlines
        if ($group) {
            $taskDeadlines = $group->groupMilestones->flatMap->groupTasks
                ->where('deadline', '!=', null)
                ->map(function($task) {
                    return (object)[
                        'title' => $task->milestoneTask->name,
                        'description' => 'Task deadline',
                        'due_date' => $task->deadline,
                        'is_overdue' => $task->deadline && $task->deadline->isPast(),
                        'is_due_soon' => $task->deadline && $task->deadline->diffInDays(now()) <= 3,
                        'type' => 'task'
                    ];
                });
                
            $deadlines = $deadlines->merge($taskDeadlines);
        }
        
        // Sort by due date and take top 5
        return $deadlines->sortBy('due_date')->take(5);
    }

    // ✅ NEW: Get adviser information
    private function getAdviserInfo($group = null)
    {
        if (!$group) {
            return [
                'has_adviser' => false,
                'adviser' => null,
                'invitations' => collect(),
                'can_invite' => false
            ];
        }

        return [
            'has_adviser' => $group->adviser !== null,
            'adviser' => $group->adviser,
            'invitations' => $group->adviserInvitations->where('status', 'pending'),
            'can_invite' => $group->adviser === null && $group->adviserInvitations->where('status', 'pending')->count() === 0
        ];
    }

    // ✅ NEW: Get defense information
    private function getDefenseInfo($group = null)
    {
        if (!$group) {
            return [
                'scheduled_defenses' => collect(),
                'pending_requests' => collect(),
                'can_request' => false
            ];
        }

        return [
            'scheduled_defenses' => $group->defenseSchedules->where('status', 'scheduled'),
            'pending_requests' => $group->defenseRequests->where('status', 'pending'),
            'can_request' => $group->adviser !== null
        ];
    }

    // ✅ NEW: Get notifications
    private function getNotifications($student)
    {
        if (!$student) return collect();

        return Notification::where('role', 'student')
            ->where('is_read', false)
            ->latest()
            ->take(5)
            ->get();
    }

    // ✅ NEW: Get existing proposal for 60% defense readiness
    private function getExistingProposal($student)
    {
        if (!$student) return null;

        return ProjectSubmission::where('student_id', $student->id)
            ->where('type', 'proposal')
            ->latest()
            ->first();
    }
}
