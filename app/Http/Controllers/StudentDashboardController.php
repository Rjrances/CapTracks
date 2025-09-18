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
        if (Auth::check()) {
            $user = Auth::user();
            $student = $user->student;
        } else {
            if (session('is_student') && session('student_id')) {
                $student = Student::find(session('student_id'));
            } else {
                return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
            }
        }
        $group = $student->groups()->with(['adviser', 'adviserInvitations.faculty', 'defenseRequests', 'defenseSchedules', 'offering.teacher'])->first();
        $overallProgress = $this->calculateOverallProgress($student, $group);
        $taskStats = $this->getTaskStatistics($student, $group);
        $submissionsCount = $this->getSubmissionsCount($student);
        $milestoneInfo = $this->getCurrentMilestoneInfo($student, $group);
        $recentTasks = $this->getRecentTasks($student, $group);
        $recentActivities = $this->getRecentActivities($student);
        $upcomingDeadlines = $this->getUpcomingDeadlines($student, $group);
        $adviserInfo = $this->getAdviserInfo($group);
        $defenseInfo = $this->getDefenseInfo($group);
        $notifications = $this->getNotifications($student);
        $existingProposal = $this->getExistingProposal($student);
        $offeringInfo = $this->getOfferingInfo($group);
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
            'existingProposal',
            'offeringInfo'
        ));
    }
    private function calculateOverallProgress($student, $group = null)
    {
        if (!$student) return 0;
        if ($group && $group->groupMilestones->count() > 0) {
            $totalProgress = $group->groupMilestones->sum('progress_percentage');
            return round($totalProgress / $group->groupMilestones->count());
        }
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
    private function getSubmissionsCount($student)
    {
        if (!$student) return 0;
        return ProjectSubmission::where('student_id', $student->id)->count();
    }
    private function getCurrentMilestoneInfo($student, $group = null)
    {
        if (!$student) {
            return [
                'name' => 'Not Started',
                'description' => 'No milestone information available',
                'progress' => 0
            ];
        }
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
    private function getRecentTasks($student, $group = null)
    {
        if (!$student) return collect();
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
    private function getRecentActivities($student)
    {
        if (!$student) return collect();
        $activities = collect();
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
        return $activities->sortByDesc('created_at')->take(5);
    }
    private function getUpcomingDeadlines($student, $group = null)
    {
        if (!$student) return collect();
        $deadlines = collect();
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
        return $deadlines->sortBy('due_date')->take(5);
    }
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
    private function getNotifications($student)
    {
        if (!$student) return collect();
        return Notification::where('role', 'student')
            ->where('is_read', false)
            ->latest()
            ->take(5)
            ->get();
    }
    private function getExistingProposal($student)
    {
        if (!$student) return null;
        return ProjectSubmission::where('student_id', $student->id)
            ->where('type', 'proposal')
            ->latest()
            ->first();
    }
    private function getOfferingInfo($group = null)
    {
        // First check if student is directly enrolled in an offering
        $student = null;
        if (Auth::check()) {
            $student = Auth::user()->student ?? null;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        
        $offering = $student ? $student->getCurrentOffering() : null;
        
        if (!$offering) {
            return [
                'has_offering' => false,
                'offer_code' => null,
                'subject_code' => null,
                'subject_title' => null,
                'teacher_name' => null,
                'coordinator_name' => null
            ];
        }
        
        return [
            'has_offering' => true,
            'offer_code' => $offering->offer_code,
            'subject_code' => $offering->subject_code,
            'subject_title' => $offering->subject_title,
            'teacher_name' => $offering->teacher_name,
            'coordinator_name' => $offering->coordinator_name
        ];
    }
}
