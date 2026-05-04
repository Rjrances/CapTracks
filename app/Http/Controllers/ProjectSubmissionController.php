<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Group;
use App\Models\ProjectSubmission;
use App\Models\Student;
use App\Services\DocumentPreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class ProjectSubmissionController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isTeacher()) {
                // Adviser project listing is consolidated into Adviser Groups page.
                return redirect()->route('adviser.groups');
            } else {
                return $this->studentIndex($user);
            }
        } else {
            if (Auth::guard('student')->check()) {
                $studentAccount = Auth::guard('student')->user();
                $student = $studentAccount->student;
                return $this->studentIndexFromSession($student);
            } else {
                return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
            }
        }
    }
    private function adviserIndex($user, Request $request)
    {
        $selectedGroupId = $request->query('group');
        $adviserGroups = Group::with(['members', 'members.submissions'])
            ->where('faculty_id', $user->faculty_id)
            ->get();
        $panelGroups = collect();
        $allGroups = $adviserGroups->values();

        if ($selectedGroupId) {
            $allGroups = $allGroups->where('id', (int) $selectedGroupId)->values();
            $adviserGroups = $adviserGroups->where('id', (int) $selectedGroupId)->values();
        }

        $memberIds = collect();
        foreach ($allGroups as $group) {
            $memberIds = $memberIds->merge($group->members->pluck('id'));
        }
        $submissions = ProjectSubmission::whereIn('student_id', $memberIds)
            ->orderBy('submitted_at', 'desc')
            ->get();
        $submissionsByGroup = $allGroups->mapWithKeys(function ($group) use ($user) {
            $groupSubmissions = $group->members->flatMap(function ($member) {
                return $member->submissions ?? collect();
            });
            $userRole = 'adviser';
            if ($group->faculty_id !== $user->faculty_id) {
                $userRole = 'panel';
            }
            return [$group->id => [
                'group' => $group,
                'submissions' => $groupSubmissions->sortByDesc('submitted_at'),
                'user_role' => $userRole
            ]];
        });
        return view('adviser.project.index', compact('allGroups', 'adviserGroups', 'panelGroups', 'submissions', 'submissionsByGroup', 'selectedGroupId'));
    }
    private function studentIndex($user)
    {
        $student = $user->student ?? null;
        $submissions = $student ? ProjectSubmission::where('student_id', $student->student_id)->orderBy('submitted_at', 'desc')->get() : [];
        return view('student.project.index', compact('submissions'));
    }
    private function studentIndexFromSession($student)
    {
        $submissions = $student ? ProjectSubmission::where('student_id', $student->student_id)->orderBy('submitted_at', 'desc')->get() : [];
        return view('student.project.index', compact('submissions'));
    }
    public function create()
    {
        return view('student.project.create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,zip,pptx,ppt|max:10240',
            'type' => 'required|in:proposal,final,other',
            'description' => 'nullable|string|max:1000',
        ]);
        
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $path = $request->file('file')->store('submissions', 'public');
        $nextVersion = ProjectSubmission::getNextVersionFor($student->student_id, $request->type);

        $submission = ProjectSubmission::create([
            'student_id' => $student->student_id,
            'file_path' => $path,
            'type' => $request->type,
            'version' => $nextVersion,
            'status' => 'pending',
            'submitted_at' => now(),
            'title' => $this->getSubmissionTitle($request->type),
            'objectives' => $request->description,
        ]);

        ActivityLog::create([
            'student_id' => $student->student_id,
            'action' => 'submission_uploaded',
            'description' => 'Uploaded ' . ($submission->title ?? 'project submission') . ' (v' . ($submission->version ?? 1) . ')',
            'loggable_type' => ProjectSubmission::class,
            'loggable_id' => $submission->id,
        ]);

        return redirect()->route('student.project')->with('success', 'Submission uploaded successfully!');
    }
    private function getSubmissionTitle($type)
    {
        return match($type) {
            'proposal' => 'Project Proposal',
            'final' => 'Final Project Report',
            'other' => 'Additional Project File',
            default => 'Project Submission'
        };
    }
    public function show($id)
    {
        $submission = ProjectSubmission::findOrFail($id);
        $viewMode = 'adviser';
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isTeacher()) {
                $hasAdviserAccess = Group::where('faculty_id', $user->faculty_id)
                    ->whereHas('members', function($query) use ($submission) {
                        $query->where('students.student_id', $submission->student_id);
                    })->exists();

                $hasAcceptedPanelAccess = Group::whereHas('members', function ($query) use ($submission) {
                        $query->where('students.student_id', $submission->student_id);
                    })
                    ->whereHas('defenseSchedules.defensePanels', function ($query) use ($user) {
                        $query->whereIn('role', ['chair', 'member'])
                            ->where('status', 'accepted')
                            ->whereHas('faculty', function ($facultyQuery) use ($user) {
                                $facultyQuery->where('faculty_id', $user->faculty_id);
                            });
                    })
                    ->exists();

                if (!$hasAdviserAccess && !$hasAcceptedPanelAccess) {
                    abort(403, 'Unauthorized access to this submission.');
                }

                if ($hasAcceptedPanelAccess && !$hasAdviserAccess) {
                    $viewMode = 'panel';
                }
            } else {
                if ($submission->student_id !== $user->student->student_id) {
                    abort(403, 'Unauthorized access to this submission.');
                }
            }
        } else {
            if (Auth::guard('student')->check()) {
                $studentAccount = Auth::guard('student')->user();
                $student = $studentAccount->student;
                if ($submission->student_id !== $student->student_id) {
                    abort(403, 'Unauthorized access to this submission.');
                }
            } else {
                return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
            }
        }
        return view('adviser.project.show', compact('submission', 'viewMode'));
    }
    public function edit($id)
    {
        $submission = ProjectSubmission::findOrFail($id);
        $user = Auth::user();
        if ($user->isTeacher()) {
            $hasAccess = Group::where('faculty_id', $user->faculty_id)
                ->whereHas('members', function($query) use ($submission) {
                    $query->where('students.student_id', $submission->student_id);
                })->exists();
            if (!$hasAccess) {
                abort(403, 'Unauthorized access to this submission.');
            }
            return view('adviser.project.edit', compact('submission'));
        } else {
            abort(403, 'Students cannot edit submissions.');
        }
    }
    public function update(Request $request, $id)
    {
        $submission = ProjectSubmission::findOrFail($id);
        $user = Auth::user();
        if ($user->isTeacher()) {
            $hasAccess = Group::where('faculty_id', $user->faculty_id)
                ->whereHas('members', function($query) use ($submission) {
                    $query->where('students.student_id', $submission->student_id);
                })->exists();
            if (!$hasAccess) {
                abort(403, 'Unauthorized access to this submission.');
            }
            $request->validate([
                'status' => 'required|in:pending,approved,rejected',
                'teacher_comment' => 'nullable|string',
            ]);
            $submission->update([
                'status' => $request->status,
                'teacher_comment' => $request->teacher_comment,
            ]);
            return redirect()->route('adviser.project.index')->with('success', 'Submission updated successfully.');
        } else {
            abort(403, 'Students cannot update submissions.');
        }
    }
    public function destroy($id)
    {
        $submission = ProjectSubmission::findOrFail($id);
        
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        if ($submission->student_id !== $student->student_id) {
            abort(403, 'Unauthorized to delete this submission.');
        }
        $submission->delete();
        return redirect()->route('student.project')->with('success', 'Submission deleted successfully.');
    }

    public function studentPreviewSubmission(ProjectSubmission $projectSubmission)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        if ($projectSubmission->student_id !== $student->student_id) {
            abort(403);
        }
        if (!$projectSubmission->file_path || !Storage::disk('public')->exists($projectSubmission->file_path)) {
            return redirect()->route('student.project')->with('error', 'File not found.');
        }
        $typeLabel = match ($projectSubmission->type) {
            'proposal' => 'Proposal',
            'final' => 'Final report',
            'other' => 'Additional file',
            default => ucfirst((string) $projectSubmission->type),
        };

        return view('student.project.preview', [
            'panel' => DocumentPreviewService::panelForSubmission($projectSubmission),
            'typeLabel' => $typeLabel,
            'backUrl' => route('student.project'),
        ]);
    }

    public function studentCompareSubmissions($left, $right)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $a = ProjectSubmission::where('student_id', $student->student_id)->findOrFail($left);
        $b = ProjectSubmission::where('student_id', $student->student_id)->findOrFail($right);
        if ((int) $a->id === (int) $b->id) {
            return redirect()->route('student.project')->with('error', 'Choose two different submissions.');
        }
        if ($a->type !== $b->type) {
            return redirect()->route('student.project')->with('error', 'Compare two submissions of the same type.');
        }
        foreach ([$a, $b] as $submission) {
            if (!$submission->file_path || !Storage::disk('public')->exists($submission->file_path)) {
                return redirect()->route('student.project')->with('error', 'A file is missing for comparison.');
            }
        }
        $typeLabel = match ($a->type) {
            'proposal' => 'proposal',
            'final' => 'final report',
            'other' => 'additional file',
            default => (string) $a->type,
        };

        return view('student.project.compare', [
            'leftPanel' => DocumentPreviewService::panelForSubmission($a),
            'rightPanel' => DocumentPreviewService::panelForSubmission($b),
            'typeLabel' => $typeLabel,
            'backUrl' => route('student.project'),
        ]);
    }

    private function getAuthenticatedStudent(): ?Student
    {
        if (Auth::guard('student')->check()) {
            return Auth::guard('student')->user()->student;
        }

        return null;
    }
}
