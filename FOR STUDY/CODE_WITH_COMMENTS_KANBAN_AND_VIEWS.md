# Kanban flow — exact repo copies + comment inventory

This file lists **what comments actually exist** in your controllers/models/views for the milestone Kanban stack, then pastes those files **as they are in the repo** (no extra `//` lines added by documentation).

**Purpose:** Decide whether to remove or keep comments before a panel. Production code here is mostly **comment-free** except a few **JavaScript `//` lines** in the Blade view and **one PHPDoc block** on `GroupMilestone`.

---

## 1. Comment inventory (only what appears in these files today)

### `app/Http/Controllers/StudentMilestoneController.php`

| Type | Count |
|------|--------|
| `//` single-line | **0** |
| `/* */` block | **0** |
| PHPDoc `/** */` | **0** |

### `app/Models/GroupMilestoneTask.php`

| Type | Count |
|------|--------|
| `//` / `/* */` | **0** |
| PHPDoc | **0** |

### `app/Models/GroupMilestone.php`

| Location | Comment |
|----------|---------|
| Lines **107–111** | PHPDoc above `coordinatorDisplayStatus()` — describes coordinator read-only label/badge return shape (`@return array{label: string, class: string}`). |

### `resources/views/student/milestones/show.blade.php` (inline JS)

| Lines | Comment |
|-------|---------|
| **309–310** | `// Use the progress value already returned by moveTask — no extra request needed` |
| **323–324** | `// Use tag selector 'h4', NOT '.h4' (class selector) — the element has no h4 CSS class` |
| **354** | `// Update the badge count` |
| **360–361** | `// Show or hide the empty state placeholder based on card count` |

### `resources/views/student/milestones/partials/task-card.blade.php`

| Type | Notes |
|------|--------|
| Blade `{{-- --}}` | **None** |
| JS `//` | **None** (uses `console.error` only) |

---

## 2. Should you remove anything?

| Comment | Verdict |
|---------|---------|
| **PHPDoc on `coordinatorDisplayStatus`** | **Keep** — normal Laravel/IDE documentation; not suspicious. |
| **JS “no extra request”** | Optional — reminds devs why `milestone_progress` is used; safe to keep or shorten to one line. |
| **JS “h4 vs .h4”** | Useful — prevents a real bug if someone changes the selector; **keep** unless you hate inline notes. |
| **JS badge / empty-state** | Low value — could delete `//` lines without losing behavior. |

**Panel angle:** Inline comments in JS are ordinary; PHPDoc on public methods is professional. Your **PHP controllers/tasks models have no tutorial-style `//` spam.**

---

## 3. `StudentMilestoneController.php` (full — current repo)

**Path:** `app/Http/Controllers/StudentMilestoneController.php`

```php
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Group;
use App\Models\GroupMilestone;
use App\Models\GroupMilestoneTask;
use App\Models\MilestoneTemplate;
use App\Models\ProjectSubmission;
use App\Models\TaskComment;
use App\Services\ActivityLogService;
class StudentMilestoneController extends Controller
{
    public function index()
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return view('student.milestones.index', [
                'student' => $student,
                'group' => null,
                'groupMilestones' => collect(),
                'overallProgress' => 0,
                'milestoneTemplates' => collect(),
                'message' => 'You are not part of any group yet. Please join or create a group to view milestones.'
            ]);
        }
        $groupMilestones = $group->groupMilestones()->with('milestoneTemplate')->get();
        $milestoneTemplates = MilestoneTemplate::with('tasks')->get();
        $overallProgress = $this->calculateGroupProgress($group);
        $studentTasks = $this->getStudentTasks($student, $group);
        $recentSubmissions = $this->getRecentSubmissions($student);
        return view('student.milestones.index', compact(
            'student',
            'group',
            'groupMilestones',
            'overallProgress',
            'milestoneTemplates',
            'studentTasks',
            'recentSubmissions'
        ));
    }
    public function show($milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $groupMilestone = $group->groupMilestones()->with(['milestoneTemplate.tasks', 'groupTasks.submissions'])->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        $tasks = $this->getMilestoneTasksByStatus($groupMilestone, $student);
        $progress = $this->calculateMilestoneProgress($groupMilestone);
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        return view('student.milestones.show', compact(
            'student',
            'group',
            'groupMilestone',
            'tasks',
            'progress',
            'isGroupLeader'
        ));
    }
    public function edit($milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isGroupLeader) {
            return redirect()->route('student.milestones')->withErrors(['auth' => 'Only group leaders can edit milestones.']);
        }
        $groupMilestone = $group->groupMilestones()->with(['milestoneTemplate', 'groupTasks'])->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        return view('student.milestones.edit', compact(
            'student',
            'group',
            'groupMilestone'
        ));
    }
    public function update(Request $request, $milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isGroupLeader) {
            return redirect()->route('student.milestones')->withErrors(['auth' => 'Only group leaders can edit milestones.']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after:today',
        ]);
        $groupMilestone->update([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
        ]);
        return redirect()->route('student.milestones')
            ->with('success', 'Milestone updated successfully!');
    }
    public function destroy($milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isGroupLeader) {
            return redirect()->route('student.milestones')->withErrors(['auth' => 'Only group leaders can delete milestones.']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        $groupMilestone->groupTasks()->delete();
        $groupMilestone->delete();
        return redirect()->route('student.milestones')
            ->with('success', 'Milestone deleted successfully!');
    }
    public function moveTask(Request $request, $taskId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Not authenticated']);
        }
        $task = GroupMilestoneTask::find($taskId);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found']);
        }
        $group = $student->groups()->first();
        if (!$group || $task->groupMilestone->group_id !== $group->id) {
            return response()->json(['success' => false, 'message' => 'Not authorized']);
        }
        $request->validate([
            'status' => 'required|in:pending,doing,done'
        ]);
        $task->updateStatus($request->status);
        return response()->json([
            'success' => true,
            'message' => 'Task moved successfully',
            'task' => $task->fresh(),
            'milestone_progress' => $task->groupMilestone->calculateProgressPercentage()
        ]);
    }
    public function bulkUpdateTasks(Request $request, $milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Not authenticated']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return response()->json(['success' => false, 'message' => 'You are not part of any group']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            return response()->json(['success' => false, 'message' => 'Milestone not found']);
        }
        $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:group_milestone_tasks,id',
            'tasks.*.status' => 'required|in:pending,doing,done'
        ]);
        foreach ($request->tasks as $taskData) {
            $task = GroupMilestoneTask::find($taskData['id']);
            if ($task && $task->groupMilestone->group_id === $group->id) {
                $task->updateStatus($taskData['status']);
            }
        }
        $groupMilestone->calculateProgressPercentage();
        return response()->json([
            'success' => true,
            'message' => 'Tasks updated successfully',
            'milestone_progress' => $groupMilestone->progress_percentage
        ]);
    }
    public function recomputeProgress(Request $request, $milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            return redirect()->route('student.milestones')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'You are not part of any group'], 403);
            }

            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Milestone not found'], 404);
            }

            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        $progress = $groupMilestone->calculateProgressPercentage();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Progress recomputed successfully',
                'progress' => $progress
            ]);
        }

        return redirect()->route('student.milestones.show', $milestoneId)
            ->with('success', 'Progress recomputed successfully!');
    }
    public function updateTask(Request $request, $taskId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Not authenticated']);
        }
        $task = GroupMilestoneTask::find($taskId);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found']);
        }
        $group = $student->groups()->first();
        if (!$group || $task->groupMilestone->group_id !== $group->id) {
            return response()->json(['success' => false, 'message' => 'Not authorized']);
        }
        $task->update([
            'is_completed' => $request->input('is_completed', false),
            'status' => $request->input('is_completed', false) ? 'done' : 'pending',
            'completed_at' => $request->input('is_completed', false) ? now() : null,
            'completed_by' => $request->input('is_completed', false) ? $student->student_id : null,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'task' => $task
        ]);
    }
    public function storeTaskComment(Request $request, GroupMilestoneTask $groupMilestoneTask)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group || (int) $groupMilestoneTask->groupMilestone->group_id !== (int) $group->id) {
            return redirect()->back()->withErrors(['auth' => 'You are not authorized to comment on this task.']);
        }
        $request->validate([
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:task_comments,id',
        ]);
        if ($request->filled('parent_id')) {
            $parentComment = TaskComment::find($request->parent_id);
            if (!$parentComment || (int) $parentComment->group_milestone_task_id !== (int) $groupMilestoneTask->id) {
                return back()->withErrors(['body' => 'Invalid reply target.'])->withInput();
            }
        }
        TaskComment::create([
            'group_milestone_task_id' => $groupMilestoneTask->id,
            'user_id' => null,
            'student_id' => $student->student_id,
            'body' => $request->body,
            'parent_id' => $request->parent_id,
        ]);
        ActivityLogService::logTaskCommentAdded($groupMilestoneTask, null, $student->student_id);

        return back()->with('success', 'Comment posted successfully.');
    }

    private function getAuthenticatedStudent()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            return $studentAccount->student;
        }
        return null;
    }
    private function calculateGroupProgress($group)
    {
        $groupMilestones = $group->groupMilestones;
        if ($groupMilestones->isEmpty()) {
            return 0;
        }
        $totalProgress = $groupMilestones->sum('progress_percentage');
        return round($totalProgress / $groupMilestones->count());
    }
    private function getStudentTasks($student, $group)
    {
        $assignedTasks = GroupMilestoneTask::whereHas('groupMilestone', function($query) use ($group) {
            $query->where('group_id', $group->id);
        })->where('assigned_to', $student->student_id)->get();
        if ($assignedTasks->isEmpty()) {
            $assignedTasks = GroupMilestoneTask::whereHas('groupMilestone', function($query) use ($group) {
                $query->where('group_id', $group->id);
            })->get();
        }
        return $assignedTasks;
    }
    private function getRecentSubmissions($student)
    {
        return ProjectSubmission::where('student_id', $student->student_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }
    private function getMilestoneTasksByStatus($groupMilestone, $student)
    {
        $tasks = GroupMilestoneTask::where('group_milestone_id', $groupMilestone->id)
            ->with([
                'milestoneTask',
                'assignedStudent',
                'submissions',
                'taskComments' => function ($query) {
                    $query->whereNull('parent_id')
                        ->with([
                            'user',
                            'studentAuthor',
                            'children.user',
                            'children.studentAuthor',
                        ]);
                },
            ])
            ->withCount('taskComments')
            ->get()
            ->map(function($task) use ($student) {
                $task->is_assigned_to_me = $task->assigned_to == $student->student_id;
                if (!$task->status) {
                    $task->status = $task->is_completed ? 'done' : 'pending';
                    $task->save();
                }
                return $task;
            });
        return [
            'pending' => $tasks->where('status', 'pending'),
            'doing' => $tasks->where('status', 'doing'),
            'done' => $tasks->where('status', 'done')
        ];
    }

    private function calculateMilestoneProgress($groupMilestone)
    {
        $tasks = GroupMilestoneTask::where('group_milestone_id', $groupMilestone->id)->get();
        if ($tasks->isEmpty()) {
            return 0;
        }
        $completedTasks = $tasks->where('status', 'done')->count();
        return round(($completedTasks / $tasks->count()) * 100);
    }
    public function updateMultipleTasks(Request $request, $milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        $tasks = GroupMilestoneTask::where('group_milestone_id', $groupMilestone->id)->get();
        $completedTaskIds = $request->input('completed_tasks', []);
        foreach ($tasks as $task) {
            $isCompleted = in_array($task->id, $completedTaskIds);
            if ($task->assigned_to === null || $task->assigned_to === $student->student_id) {
                $task->update([
                    'is_completed' => $isCompleted,
                    'status' => $isCompleted ? 'done' : 'pending',
                    'completed_at' => $isCompleted ? now() : null,
                    'completed_by' => $isCompleted ? $student->student_id : null,
                ]);
            }
        }
        $groupMilestone->calculateProgressPercentage();
        return redirect()->route('student.milestones.show', $milestoneId)
            ->with('success', 'Task progress updated successfully!');
    }
    public function assignTask(Request $request, GroupMilestoneTask $groupMilestoneTask)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isLeader) {
            return redirect()->back()->withErrors(['auth' => 'Only group leaders can assign tasks.']);
        }
        $request->validate([
            'assigned_to' => 'required|exists:students,student_id',
        ]);
        $isGroupMember = $group->members()->where('group_members.student_id', $request->assigned_to)->exists();
        if (!$isGroupMember) {
            return redirect()->back()->withErrors(['assigned_to' => 'Student must be a member of this group.']);
        }
        $groupMilestoneTask->update([
            'assigned_to' => $request->assigned_to,
        ]);
        return redirect()->back()->with('success', 'Task assigned successfully.');
    }
    public function unassignTask(GroupMilestoneTask $groupMilestoneTask)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isLeader) {
            return redirect()->back()->withErrors(['auth' => 'Only group leaders can unassign tasks.']);
        }
        $groupMilestoneTask->update([
            'assigned_to' => null,
        ]);
        return redirect()->back()->with('success', 'Task unassigned successfully.');
    }
}
```

---

## 4. `GroupMilestone.php` (full — includes the only PHPDoc in this stack)

**Path:** `app/Models/GroupMilestone.php`

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class GroupMilestone extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id',
        'milestone_template_id',
        'title',
        'description',
        'progress_percentage',
        'start_date',
        'target_date',
        'due_date',
        'completed_date',
        'status',
        'notes'
    ];
    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
        'progress_percentage' => 'integer'
    ];
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    public function milestoneTemplate()
    {
        return $this->belongsTo(MilestoneTemplate::class);
    }
    public function template()
    {
        return $this->belongsTo(MilestoneTemplate::class, 'milestone_template_id');
    }
    public function groupTasks()
    {
        return $this->hasMany(GroupMilestoneTask::class);
    }
    public function calculateProgressPercentage()
    {
        $totalTasks = $this->groupTasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }
        $completedTasks = $this->groupTasks()->where('status', 'done')->count();
        $percentage = round(($completedTasks / $totalTasks) * 100);
        $this->update(['progress_percentage' => $percentage]);
        return $percentage;
    }
    public function getIsCompletedAttribute()
    {
        return $this->progress_percentage >= 100;
    }
    public function getStatusTextAttribute()
    {
        if ($this->progress_percentage >= 100) {
            return 'Completed';
        } elseif ($this->progress_percentage >= 80) {
            return 'Almost Done';
        } elseif ($this->progress_percentage >= 50) {
            return 'In Progress';
        } elseif ($this->progress_percentage > 0) {
            return 'Started';
        } else {
            return 'Not Started';
        }
    }
    public function getIsOverdueAttribute()
    {
        return $this->target_date && $this->target_date->isPast() && $this->progress_percentage < 100;
    }
    public function getDaysRemainingAttribute()
    {
        if (!$this->target_date) {
            return null;
        }
        return now()->diffInDays($this->target_date, false);
    }

    public function totalTasksCount(): int
    {
        return $this->relationLoaded('groupTasks')
            ? $this->groupTasks->count()
            : $this->groupTasks()->count();
    }

    public function completedTasksCount(): int
    {
        if ($this->relationLoaded('groupTasks')) {
            return $this->groupTasks
                ->filter(fn (GroupMilestoneTask $t) => $t->status === 'done' || $t->is_completed)
                ->count();
        }

        return $this->groupTasks()
            ->where(function ($q) {
                $q->where('status', 'done')->orWhere('is_completed', true);
            })
            ->count();
    }

    /**
     * Label + badge class for coordinator read-only view (aligned with task + percent progress).
     *
     * @return array{label: string, class: string}
     */
    public function coordinatorDisplayStatus(): array
    {
        $pct = (int) $this->progress_percentage;
        $total = $this->totalTasksCount();
        $done = $this->completedTasksCount();

        if ($total > 0) {
            if ($done >= $total) {
                return ['label' => 'Completed', 'class' => 'success'];
            }
            if ($done > 0 || $pct > 0) {
                return ['label' => 'In progress', 'class' => 'info'];
            }

            return ['label' => 'Not started', 'class' => 'secondary'];
        }

        if ($pct >= 100) {
            return ['label' => 'Completed', 'class' => 'success'];
        }
        if ($pct > 0) {
            return ['label' => 'In progress', 'class' => 'info'];
        }

        return ['label' => 'Not started', 'class' => 'secondary'];
    }
}
```

---

## 5. `GroupMilestoneTask.php` (full — no comments)

**Path:** `app/Models/GroupMilestoneTask.php`

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class GroupMilestoneTask extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_milestone_id',
        'milestone_task_id',
        'assigned_to',
        'is_completed',
        'status',
        'completed_at',
        'completed_by',
        'notes',
        'deadline'
    ];
    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'deadline' => 'datetime'
    ];
    public function groupMilestone()
    {
        return $this->belongsTo(GroupMilestone::class);
    }
    public function milestoneTask()
    {
        return $this->belongsTo(MilestoneTask::class);
    }
    public function assignedStudent()
    {
        return $this->belongsTo(Student::class, 'assigned_to', 'student_id');
    }
    public function completedByStudent()
    {
        return $this->belongsTo(Student::class, 'completed_by', 'student_id');
    }
    public function submissions()
    {
        return $this->hasMany(TaskSubmission::class);
    }

    public function taskComments()
    {
        return $this->hasMany(TaskComment::class);
    }
    public function markAsCompleted($completedBy = null)
    {
        $this->update([
            'is_completed' => true,
            'status' => 'done',
            'completed_at' => now(),
            'completed_by' => $completedBy
        ]);
        $this->groupMilestone->calculateProgressPercentage();
    }
    public function markAsIncomplete()
    {
        $this->update([
            'is_completed' => false,
            'status' => 'pending',
            'completed_at' => null,
            'completed_by' => null
        ]);
        $this->groupMilestone->calculateProgressPercentage();
    }
    public function updateStatus($status)
    {
        $this->update([
            'status' => $status,
            'is_completed' => $status === 'done'
        ]);
        $this->groupMilestone->calculateProgressPercentage();
    }
    public function getIsOverdueAttribute()
    {
        return $this->deadline && $this->deadline->isPast() && !$this->is_completed;
    }
    public function getDaysRemainingAttribute()
    {
        if (!$this->deadline) {
            return null;
        }
        return now()->diffInDays($this->deadline, false);
    }
    public function getStatusTextAttribute()
    {
        if ($this->status === 'done') {
            return 'Completed';
        } elseif ($this->status === 'doing') {
            return 'In Progress';
        } elseif ($this->is_overdue) {
            return 'Overdue';
        } else {
            return 'Pending';
        }
    }
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'done' => 'success',
            'doing' => 'warning',
            'pending' => 'secondary',
            default => 'secondary'
        };
    }
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    public function scopeDoing($query)
    {
        return $query->where('status', 'doing');
    }
    public function scopeDone($query)
    {
        return $query->where('status', 'done');
    }
}
```

---

## 6. Views (verbatim — all `//` / Blade comments as in repo)

### `resources/views/student/milestones/show.blade.php`

```blade
@extends('layouts.student')
@section('title', $groupMilestone->title ?? $groupMilestone->milestoneTemplate->name)
@section('content')
<div class="container-fluid mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Kanban board for milestone tasks</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.project') }}" class="btn btn-outline-primary">
                <i class="fas fa-file-upload me-2"></i>View Project Submissions
            </a>
            <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Milestones
            </a>
        </div>
    </div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-0">
                        <i class="fas fa-flag me-2"></i>Milestone Information
                    </h5>
                    <small>{{ $groupMilestone->description ?? $groupMilestone->milestoneTemplate->description ?? 'No description provided' }}</small>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="me-3">
                            <h4 class="mb-0 {{ $progress >= 80 ? 'text-success' : ($progress >= 50 ? 'text-warning' : 'text-danger') }}">
                                {{ $progress }}%
                            </h4>
                            <small class="text-white-50">Complete</small>
                        </div>
                        <div class="progress flex-grow-1" style="height: 25px; max-width: 200px;">
                            <div class="progress-bar {{ $progress >= 80 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                 role="progressbar" 
                                 style="width: {{ $progress }}%" 
                                 aria-valuenow="{{ $progress }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card kanban-column" data-status="pending">
                <div class="card-header bg-secondary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Pending
                        </h6>
                        <span class="badge bg-light text-dark">{{ $tasks['pending']->count() }}</span>
                    </div>
                </div>
                <div class="card-body kanban-column-body" style="min-height: 400px;">
                    @foreach($tasks['pending'] as $task)
                        @include('student.milestones.partials.task-card', ['task' => $task, 'isGroupLeader' => $isGroupLeader, 'student' => $student])
                    @endforeach
                    <div class="kanban-empty-state text-center text-muted py-4" style="{{ $tasks['pending']->count() === 0 ? '' : 'display:none;' }}">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No pending tasks</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kanban-column" data-status="doing">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-play me-2"></i>In Progress
                        </h6>
                        <span class="badge bg-light text-dark">{{ $tasks['doing']->count() }}</span>
                    </div>
                </div>
                <div class="card-body kanban-column-body" style="min-height: 400px;">
                    @foreach($tasks['doing'] as $task)
                        @include('student.milestones.partials.task-card', ['task' => $task, 'isGroupLeader' => $isGroupLeader, 'student' => $student])
                    @endforeach
                    <div class="kanban-empty-state text-center text-muted py-4" style="{{ $tasks['doing']->count() === 0 ? '' : 'display:none;' }}">
                        <i class="fas fa-spinner fa-2x mb-2"></i>
                        <p class="mb-0">No tasks in progress</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kanban-column" data-status="done">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-check me-2"></i>Completed
                        </h6>
                        <span class="badge bg-light text-dark">{{ $tasks['done']->count() }}</span>
                    </div>
                </div>
                <div class="card-body kanban-column-body" style="min-height: 400px;">
                    @foreach($tasks['done'] as $task)
                        @include('student.milestones.partials.task-card', ['task' => $task, 'isGroupLeader' => $isGroupLeader, 'student' => $student])
                    @endforeach
                    <div class="kanban-empty-state text-center text-muted py-4" style="{{ $tasks['done']->count() === 0 ? '' : 'display:none;' }}">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p class="mb-0">No completed tasks</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@php
    $allTasksForComments = collect($tasks['pending'])->concat($tasks['doing'])->concat($tasks['done']);
@endphp
@foreach($allTasksForComments as $task)
    <div class="modal fade" id="taskCommentsModal{{ $task->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskCommentsModalLabel{{ $task->id }}">
                        <i class="fas fa-comments me-2"></i>Discussion — {{ $task->milestoneTask->name ?? 'Task' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('partials.task-comments-thread', [
                        'comments' => $task->taskComments,
                        'formAction' => route('student.milestones.task-comments.store', $task),
                    ])
                </div>
            </div>
        </div>
    </div>
@endforeach
@if($isGroupLeader)
    @php
        $allTasks = collect($tasks['pending'])->concat($tasks['doing'])->concat($tasks['done']);
    @endphp
    @foreach($allTasks as $task)
        <div class="modal fade" id="assignTaskModal{{ $task->id }}" tabindex="-1" aria-labelledby="assignTaskModalLabel{{ $task->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignTaskModalLabel{{ $task->id }}">
                            {{ $task->assigned_to ? 'Reassign Task' : 'Assign Task' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('student.milestones.assign-task', $task->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body">
                            <h6>{{ $task->milestoneTask->name }}</h6>
                            <p class="text-muted">{{ $task->milestoneTask->description }}</p>
                            @if($task->assigned_to)
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Currently assigned to: <strong>{{ $task->assignedStudent->name ?? 'Unknown' }}</strong>
                                </div>
                            @endif
                            <div class="mb-3">
                                <label for="assigned_to_{{ $task->id }}" class="form-label">
                                    {{ $task->assigned_to ? 'Reassign to:' : 'Assign to:' }}
                                </label>
                                <select class="form-select" id="assigned_to_{{ $task->id }}" name="assigned_to" required>
                                    <option value="">Select a group member</option>
                                    @foreach($group->members as $member)
                                        <option value="{{ $member->student_id }}" 
                                                {{ $task->assigned_to == $member->student_id ? 'selected' : '' }}>
                                            {{ $member->name }} ({{ $member->pivot->role }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                {{ $task->assigned_to ? 'Reassign Task' : 'Assign Task' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
@push('styles')
<style>
.kanban-column {
    height: 100%;
}
.kanban-column-body {
    overflow-y: auto;
    max-height: 600px;
}
.task-card {
    cursor: grab;
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
    margin-bottom: 1rem;
}
.task-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.task-card.dragging {
    opacity: 0.5;
    cursor: grabbing;
}
.kanban-column.drag-over {
    background-color: #f8f9fa;
    border: 2px dashed #007bff;
}
.task-card-header {
    display: flex;
    justify-content-between;
    align-items: flex-start;
}
.task-card-content {
    flex-grow: 1;
}
.task-card-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.status-badge {
    font-size: 0.75rem;
}
.progress-sm {
    height: 8px;
}
.task-meta {
    font-size: 0.875rem;
    color: #6c757d;
}
.task-assignee {
    font-size: 0.875rem;
    font-weight: 500;
}
.task-deadline {
    font-size: 0.75rem;
}
.task-deadline.overdue {
    color: #dc3545;
}
.task-notes {
    background-color: #f8f9fa;
    border-left: 3px solid #007bff;
    padding: 0.5rem;
    margin: 0.5rem 0;
    font-size: 0.875rem;
}
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const columns = document.querySelectorAll('.kanban-column-body');
    columns.forEach(column => {
        new Sortable(column, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function(evt) {
                const taskId = evt.item.dataset.taskId;
                const newStatus = evt.to.closest('.kanban-column').dataset.status;
                moveTask(taskId, newStatus);
            }
        });
    });
    columns.forEach(column => {
        column.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.closest('.kanban-column').classList.add('drag-over');
        });
        column.addEventListener('dragleave', function(e) {
            this.closest('.kanban-column').classList.remove('drag-over');
        });
        column.addEventListener('drop', function(e) {
            this.closest('.kanban-column').classList.remove('drag-over');
        });
    });
});
function moveTask(taskId, newStatus) {
    fetch(`/student/milestones/tasks/${taskId}/move`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Task moved successfully!', 'success');
            // Use the progress value already returned by moveTask — no extra request needed
            updateProgressBarUI(data.milestone_progress);
            updateColumnCounts();
        } else {
            showAlert('Failed to move task: ' + data.message, 'danger');
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(() => {
        showAlert('Error moving task. Please try again.', 'danger');
        setTimeout(() => location.reload(), 1000);
    });
}

function updateProgressBarUI(progress) {
    // Use tag selector 'h4', NOT '.h4' (class selector) — the element has no h4 CSS class
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.querySelector('h4.mb-0');

    if (!progressBar || !progressText) {
        return;
    }

    progressBar.style.width = progress + '%';
    progressBar.setAttribute('aria-valuenow', progress);
    progressText.textContent = progress + '%';
    progressBar.className = 'progress-bar';

    if (progress >= 80) {
        progressBar.classList.add('bg-success');
        progressText.className = 'mb-0 text-success';
    } else if (progress >= 50) {
        progressBar.classList.add('bg-warning');
        progressText.className = 'mb-0 text-warning';
    } else {
        progressBar.classList.add('bg-danger');
        progressText.className = 'mb-0 text-danger';
    }
}

function updateColumnCounts() {
    document.querySelectorAll('.kanban-column').forEach(column => {
        const taskCards = column.querySelectorAll('.task-card');
        const count = taskCards.length;

        // Update the badge count
        const badge = column.querySelector('.card-header .badge');
        if (badge) {
            badge.textContent = count;
        }

        // Show or hide the empty state placeholder based on card count
        const emptyState = column.querySelector('.kanban-empty-state');
        if (emptyState) {
            emptyState.style.display = count === 0 ? '' : 'none';
        }
    });
}

function recomputeProgress() {
    fetch(`/student/milestones/{{ $groupMilestone->id }}/recompute-progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            updateProgressBarUI(data.progress);
        } else {
            showAlert('Failed to recompute progress: ' + data.message, 'danger');
        }
    })
    .catch(() => {
        showAlert('Error recomputing progress. Please try again.', 'danger');
    });
}
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endpush
@endsection
```

### `resources/views/student/milestones/partials/task-card.blade.php`

```blade
<div class="card task-card" data-task-id="{{ $task->id }}" draggable="true">
    <div class="card-body p-3">
        <div class="task-card-header">
            <div class="task-card-content">
                <h6 class="mb-2 {{ $task->status === 'done' ? 'text-decoration-line-through text-muted' : '' }}">
                    {{ $task->milestoneTask->name ?? 'Task' }}
                </h6>
                <p class="text-muted mb-2 small">
                    {{ Str::limit($task->milestoneTask->description ?? 'No description', 100) }}
                </p>
                <div class="mb-2">
                    @if($task->is_assigned_to_me)
                        <span class="badge bg-info status-badge">
                            <i class="fas fa-user me-1"></i>Assigned to you
                        </span>
                    @elseif($task->assigned_to)
                        <span class="badge bg-secondary status-badge">
                            <i class="fas fa-user me-1"></i>{{ $task->assignedStudent->name ?? 'Assigned' }}
                        </span>
                    @else
                        <span class="badge bg-light text-dark status-badge">
                            <i class="fas fa-user-slash me-1"></i>Unassigned
                        </span>
                    @endif
                </div>
                @if($task->notes)
                    <div class="task-notes">
                        <small><strong>Notes:</strong> {{ Str::limit($task->notes, 80) }}</small>
                    </div>
                @endif
                <div class="task-meta">
                    @if($task->deadline)
                        <div class="task-deadline {{ $task->is_overdue ? 'overdue' : '' }}">
                            <i class="fas fa-calendar me-1"></i>
                            {{ $task->deadline ? \Carbon\Carbon::parse($task->deadline)->format('M d, Y') : 'TBA' }}
                            @if($task->is_overdue)
                                <span class="text-danger ms-1">(Overdue)</span>
                            @endif
                        </div>
                    @endif
                    @if($task->completed_at)
                        <div class="task-meta">
                            <small class="text-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Completed {{ $task->completed_at ? \Carbon\Carbon::parse($task->completed_at)->format('M d, Y') : 'Recently' }}
                            </small>
                        </div>
                    @endif
                </div>
                <div class="task-card-actions">
                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#taskCommentsModal{{ $task->id }}" title="Task discussion">
                        <i class="fas fa-comments"></i>
                        @if(($task->task_comments_count ?? 0) > 0)
                            <span class="badge bg-secondary ms-1">{{ $task->task_comments_count }}</span>
                        @endif
                    </button>
                    @if($isGroupLeader)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignTaskModal{{ $task->id }}" title="{{ $task->assigned_to ? 'Reassign Task' : 'Assign Task' }}">
                            <i class="fas fa-{{ $task->assigned_to ? 'user-edit' : 'user-plus' }}"></i>
                        </button>
                        @if($task->assigned_to)
                            <form action="{{ route('student.milestones.unassign-task', $task->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Unassign this task?')" title="Unassign Task">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            </form>
                        @endif
                    @endif
                    @if($task->assigned_to === null || $task->assigned_to == $student->student_id)
                        <a href="{{ route('student.task-submission.create', $task->id) }}" class="btn btn-sm btn-success" title="Submit Task">
                            <i class="fas fa-upload"></i>
                        </a>
                    @endif

                </div>
            </div>
            <div class="ms-2">
                <span class="badge bg-{{ $task->status_badge_class }} status-badge">
                    @if($task->status === 'done')
                        <i class="fas fa-check me-1"></i>Done
                    @elseif($task->status === 'doing')
                        <i class="fas fa-play me-1"></i>Doing
                    @else
                        <i class="fas fa-clock me-1"></i>Pending
                    @endif
                </span>
            </div>
        </div>
        <div class="mt-3 d-flex gap-1">
            <button type="button" 
                    class="btn btn-sm {{ $task->status === 'pending' ? 'btn-secondary' : 'btn-outline-secondary' }}"
                    onclick="changeTaskStatus({{ $task->id }}, 'pending')"
                    title="Mark as Pending">
                <i class="fas fa-clock"></i>
            </button>
            <button type="button" 
                    class="btn btn-sm {{ $task->status === 'doing' ? 'btn-warning' : 'btn-outline-warning' }}"
                    onclick="changeTaskStatus({{ $task->id }}, 'doing')"
                    title="Mark as In Progress">
                <i class="fas fa-play"></i>
            </button>
            <button type="button" 
                    class="btn btn-sm {{ $task->status === 'done' ? 'btn-success' : 'btn-outline-success' }}"
                    onclick="changeTaskStatus({{ $task->id }}, 'done')"
                    title="Mark as Done">
                <i class="fas fa-check"></i>
            </button>
        </div>
    </div>
</div>
<script>
function changeTaskStatus(taskId, newStatus) {
    fetch(`{{ url('/student/milestones/tasks') }}/${taskId}/move`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Task status updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Failed to update task status: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating task status. Please try again.', 'danger');
    });
}
</script>
```

---

*Last synced from workspace files in this session. If line numbers drift, search the repo for the quoted comment strings.*
