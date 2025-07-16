<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StudentGroupController extends Controller
{
    public function show()
    {
        $student = Auth::user()->student ?? null;
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('student_id', $student->id);
        })->with(['adviser', 'members'])->first() : null;
        return view('student.group.show', compact('group'));
    }

    public function create()
    {
        return view('student.group.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $student = Auth::user()->student;
        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);
        // Optionally assign student to group (if you have a pivot table or member relationship)
        // $group->members()->attach($student->id);
        return redirect()->route('student.group')->with('success', 'Group created successfully!');
    }

    public function edit()
    {
        $student = Auth::user()->student;
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('student_id', $student->id);
        })->first() : null;
        return view('student.group.edit', compact('group'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $student = Auth::user()->student;
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('student_id', $student->id);
        })->first() : null;
        if ($group) {
            $group->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        }
        return redirect()->route('student.group')->with('success', 'Group updated successfully!');
    }

    public function index()
    {
        $groups = \App\Models\Group::with('adviser')->get();
        return view('student.group.index', compact('groups'));
    }
}
