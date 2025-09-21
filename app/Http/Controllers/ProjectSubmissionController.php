<?php
namespace App\Http\Controllers;
use App\Models\ProjectSubmission;
use App\Models\Student;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ProjectSubmissionController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isTeacher()) {
                return $this->adviserIndex($user);
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
    private function adviserIndex($user)
    {
        $adviserGroups = Group::with(['members', 'members.submissions'])
            ->where('faculty_id', $user->faculty_id)
            ->get();
        $panelGroups = Group::with(['members', 'members.submissions'])
            ->whereHas('defenseSchedules.defensePanels', function($query) use ($user) {
                $query->where('faculty_id', $user->id);
            })
            ->get();
        $allGroups = $adviserGroups->concat($panelGroups)->unique('id');
        $memberIds = collect();
        foreach ($allGroups as $group) {
            $memberIds = $memberIds->merge($group->members->pluck('id'));
        }
        $submissions = ProjectSubmission::with(['student'])
            ->whereIn('student_id', $memberIds)
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
        return view('adviser.project.index', compact('allGroups', 'adviserGroups', 'panelGroups', 'submissions', 'submissionsByGroup'));
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
        if (Auth::check()) {
            if (Auth::guard('student')->check()) {
                $studentAccount = Auth::guard('student')->user();
                $student = $studentAccount->student;
            } else {
                $student = null;
            }
        } else {
            $student = Student::find(session('student_id'));
        }
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $path = $request->file('file')->store('submissions', 'public');
        ProjectSubmission::create([
            'student_id' => $student->student_id,
            'file_path' => $path,
            'type' => $request->type,
            'status' => 'pending',
            'submitted_at' => now(),
            'title' => $this->getSubmissionTitle($request->type),
            'objectives' => $request->description,
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
        $submission = ProjectSubmission::with('student')->findOrFail($id);
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isTeacher()) {
                $hasAccess = Group::where('faculty_id', $user->faculty_id)
                    ->whereHas('members', function($query) use ($submission) {
                        $query->where('students.student_id', $submission->student_id);
                    })->exists();
                if (!$hasAccess) {
                    abort(403, 'Unauthorized access to this submission.');
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
        return view('adviser.project.show', compact('submission'));
    }
    public function edit($id)
    {
        $submission = ProjectSubmission::with('student')->findOrFail($id);
        $user = Auth::user();
        if ($user->isTeacher()) {
            $hasAccess = Group::where('adviser_id', $user->id)
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
            $hasAccess = Group::where('adviser_id', $user->id)
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
        if (Auth::check()) {
            if (Auth::guard('student')->check()) {
                $studentAccount = Auth::guard('student')->user();
                $student = $studentAccount->student;
            } else {
                $student = null;
            }
        } else {
            $student = Student::find(session('student_id'));
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
}
