<?php

namespace App\Http\Controllers;

use App\Models\ProjectSubmission;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectSubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $student = Auth::user()->student ?? null;
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
        return view('student.project.show', compact('submission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $submission = ProjectSubmission::with('student')->findOrFail($id);
        return view('teacher.project.edit', compact('submission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $submission = ProjectSubmission::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'teacher_comment' => 'nullable|string',
        ]);
        $submission->update([
            'status' => $request->status,
            'teacher_comment' => $request->teacher_comment,
        ]);
        return redirect()->route('teacher.project.index')->with('success', 'Submission updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $submission = ProjectSubmission::findOrFail($id);
        $submission->delete();
        return redirect()->route('student.project')->with('success', 'Submission deleted.');
    }
}
