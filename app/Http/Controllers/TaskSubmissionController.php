<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\TaskSubmission;
use App\Models\ProjectSubmission;
use App\Models\GroupMilestoneTask;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class TaskSubmissionController extends Controller
{
    public function create($taskId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $task = GroupMilestoneTask::with(['groupMilestone.group', 'milestoneTask'])->findOrFail($taskId);
        $group = $student->groups()->first();
        if (!$group || $task->groupMilestone->group_id !== $group->id) {
            return redirect()->back()->withErrors(['auth' => 'You are not authorized to submit for this task.']);
        }
        if ($task->assigned_to && $task->assigned_to !== $student->student_id) {
            return redirect()->back()->withErrors(['auth' => 'This task is assigned to another group member.']);
        }
        return view('student.milestones.submit-task', compact('task', 'student'));
    }
    public function store(Request $request, $taskId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $task = GroupMilestoneTask::with(['groupMilestone.group'])->findOrFail($taskId);
        $group = $student->groups()->first();
        if (!$group || $task->groupMilestone->group_id !== $group->id) {
            return redirect()->back()->withErrors(['auth' => 'You are not authorized to submit for this task.']);
        }
        if ($task->assigned_to && $task->assigned_to !== $student->student_id) {
            return redirect()->back()->withErrors(['auth' => 'This task is assigned to another group member.']);
        }
        $request->validate([
            'submission_type' => 'required|in:document,screenshots,progress_notes',
            'description' => 'required|string|min:10',
            'notes' => 'nullable|string|max:1000',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'file' => 'required_if:submission_type,document,screenshots|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:10240', // 10MB max
        ]);
        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('task-submissions', 'public');
        }
        $taskSubmission = TaskSubmission::create([
            'group_milestone_task_id' => $taskId,
            'student_id' => $student->student_id,
            'submission_type' => $request->submission_type,
            'file_path' => $filePath,
            'description' => $request->description,
            'notes' => $request->notes,
            'progress_percentage' => $request->progress_percentage ?? 0,
            'status' => 'pending',
        ]);
        if ($filePath) {
            ProjectSubmission::create([
                'student_id' => $student->student_id,
                'file_path' => $filePath,
                'type' => 'other', // Task submissions are categorized as 'other'
                'status' => 'pending',
                'submitted_at' => now(),
                'title' => $task->milestoneTask->name ?? 'Task Submission',
                'objectives' => $request->description,
                'methodology' => $request->notes,
                'timeline' => 'Milestone: ' . ($task->groupMilestone->milestoneTemplate->name ?? 'Unknown'),
                'expected_outcomes' => 'Progress: ' . ($request->progress_percentage ?? 0) . '%',
            ]);
        }
        if ($task->status === 'pending') {
            $task->updateStatus('doing');
        }
        return redirect()->route('student.milestones.show', $task->groupMilestone->id)
            ->with('success', 'Task submission uploaded successfully! It will be reviewed by your adviser.');
    }
    public function show($submissionId)
    {
        $submission = TaskSubmission::with(['groupMilestoneTask.milestoneTask', 'student', 'reviewer'])->findOrFail($submissionId);
        $student = $this->getAuthenticatedStudent();
        if ($student && $submission->student_id === $student->student_id) {
            return view('student.milestones.submission-detail', compact('submission'));
        }
        $user = Auth::user();
        if ($user && $user->hasRole('adviser')) {
            $group = $submission->groupMilestoneTask->groupMilestone->group;
            if ($group->adviser_id === $user->id) {
                return view('adviser.task-submission-detail', compact('submission'));
            }
        }
        abort(403, 'Unauthorized');
    }
    public function review(Request $request, $submissionId)
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('adviser')) {
            return redirect()->back()->withErrors(['auth' => 'Only advisers can review submissions.']);
        }
        $submission = TaskSubmission::with(['groupMilestoneTask.groupMilestone.group'])->findOrFail($submissionId);
        if ($submission->groupMilestoneTask->groupMilestone->group->adviser_id !== $user->id) {
            return redirect()->back()->withErrors(['auth' => 'You are not the adviser for this group.']);
        }
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'adviser_feedback' => 'required|string|min:10',
        ]);
        $submission->update([
            'status' => $request->status,
            'adviser_feedback' => $request->adviser_feedback,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);
        if ($request->status === 'approved') {
            $task = $submission->groupMilestoneTask;
            $allSubmissionsApproved = $task->submissions()->where('status', '!=', 'approved')->count() === 0;
            if ($allSubmissionsApproved) {
                $task->markAsCompleted($submission->student_id);
            }
        }
        return redirect()->back()->with('success', 'Submission reviewed successfully!');
    }
    private function getAuthenticatedStudent()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            return $studentAccount->student;
        }
        return null;
    }
}
