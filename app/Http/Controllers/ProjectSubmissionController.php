<?php

namespace App\Http\Controllers;

use App\Models\ProjectSubmission;
use App\Models\Student;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectSubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Check if user is an adviser or student
        if ($user->isTeacher()) {
            // Adviser view - show all submissions from their groups
            return $this->adviserIndex($user);
        } else {
            // Student view - show only their submissions
            return $this->studentIndex($user);
        }
    }

    /**
     * Adviser view - show all submissions from groups they advise
     */
    private function adviserIndex($user)
    {
        // Get groups where user is the adviser
        $groups = Group::with(['members', 'members.submissions'])
            ->where('adviser_id', $user->id)
            ->get();

        // Get all submissions from these groups
        $groupIds = $groups->pluck('id');
        $memberIds = collect();
        
        foreach ($groups as $group) {
            $memberIds = $memberIds->merge($group->members->pluck('id'));
        }

        $submissions = ProjectSubmission::with(['student'])
            ->whereIn('student_id', $memberIds)
            ->orderBy('submitted_at', 'desc')
            ->get();

        // Group submissions by group for better organization
        $submissionsByGroup = $groups->mapWithKeys(function ($group) {
            $groupSubmissions = $group->members->flatMap(function ($member) {
                return $member->submissions ?? collect();
            });
            
            return [$group->id => [
                'group' => $group,
                'submissions' => $groupSubmissions->sortByDesc('submitted_at')
            ]];
        });

        return view('adviser.project.index', compact('groups', 'submissions', 'submissionsByGroup'));
    }

    /**
     * Student view - show only their submissions
     */
    private function studentIndex($user)
    {
        $student = $user->student ?? null;
        $submissions = $student ? ProjectSubmission::where('student_id', $student->id)->orderBy('submitted_at', 'desc')->get() : [];
        return view('student.project.index', compact('submissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('student.project.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,zip',
            'type' => 'required|in:proposal,final,other',
        ]);
        $student = Auth::user()->student;
        $path = $request->file('file')->store('submissions', 'public');
        ProjectSubmission::create([
            'student_id' => $student->id,
            'file_path' => $path,
            'type' => $request->type,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        return redirect()->route('student.project')->with('success', 'Submission uploaded successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $submission = ProjectSubmission::with('student')->findOrFail($id);
        
        // Check if user is adviser and has access to this submission
        $user = Auth::user();
        if ($user->isTeacher()) {
            $hasAccess = Group::where('adviser_id', $user->id)
                ->whereHas('members', function($query) use ($submission) {
                    $query->where('students.id', $submission->student_id);
                })->exists();
            
            if (!$hasAccess) {
                abort(403, 'Unauthorized access to this submission.');
            }
        } else {
            // Student can only view their own submissions
            if ($submission->student_id !== $user->student->id) {
                abort(403, 'Unauthorized access to this submission.');
            }
        }
        
        return view('adviser.project.show', compact('submission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $submission = ProjectSubmission::with('student')->findOrFail($id);
        
        // Check if user is adviser and has access to this submission
        $user = Auth::user();
        if ($user->isTeacher()) {
            $hasAccess = Group::where('adviser_id', $user->id)
                ->whereHas('members', function($query) use ($submission) {
                    $query->where('students.id', $submission->student_id);
                })->exists();
            
            if (!$hasAccess) {
                abort(403, 'Unauthorized access to this submission.');
            }
            
            return view('adviser.project.edit', compact('submission'));
        } else {
            abort(403, 'Students cannot edit submissions.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $submission = ProjectSubmission::findOrFail($id);
        
        // Check if user is adviser and has access to this submission
        $user = Auth::user();
        if ($user->isTeacher()) {
            $hasAccess = Group::where('adviser_id', $user->id)
                ->whereHas('members', function($query) use ($submission) {
                    $query->where('students.id', $submission->student_id);
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $submission = ProjectSubmission::findOrFail($id);
        
        // Only students can delete their own submissions
        $user = Auth::user();
        if (!$user->isStudent() || $submission->student_id !== $user->student->id) {
            abort(403, 'Unauthorized to delete this submission.');
        }
        
        $submission->delete();
        return redirect()->route('student.project')->with('success', 'Submission deleted successfully.');
    }
}
