<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offering;
use App\Models\User;
use App\Models\Schedule;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;

class ChairpersonController extends Controller
{
    public function index()
    {
        return view('chairperson.dashboard');
    }

    // ======= OFFERINGS =======

    public function indexOfferings()
    {
        $offerings = Offering::all();
        return view('chairperson.offerings.index', compact('offerings'));
    }

    public function createOffering()
    {
        return view('chairperson.offerings.create');
    }

    public function storeOffering(Request $request)
    {
        $request->validate([
            'subject_title' => 'required|string|max:255',
            'subject_code' => 'required|string|max:100',
            'teacher_name'  => 'required|string|max:255',
        ]);

        Offering::create($request->only('subject_title', 'subject_code', 'teacher_name'));

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering added successfully.');
    }

    public function editOffering($id)
    {
        $offering = Offering::findOrFail($id);
        return view('chairperson.offerings.edit', compact('offering'));
    }

    public function updateOffering(Request $request, $id)
    {
        $request->validate([
            'subject_title' => 'required|string|max:255',
            'subject_code'  => 'required|string|max:100',
            'teacher_name'  => 'required|string|max:255',
        ]);

        $offering = Offering::findOrFail($id);
        $offering->update($request->only('subject_title', 'subject_code', 'teacher_name'));

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering updated successfully.');
    }

    public function deleteOffering($id)
    {
        $offering = Offering::findOrFail($id);
        $offering->delete();

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering deleted.');
    }

    // ======= TEACHERS =======

    public function teachers()
    {
        $teachers = User::whereIn('role', ['adviser', 'panelist'])->get();
        return view('chairperson.teachers.index', compact('teachers'));
    }

    public function createTeacher()
    {
        return view('chairperson.teachers.create');
    }

    public function storeTeacher(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:adviser,panelist',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'role'                 => $request->role,
            'password'             => bcrypt($request->password),
            'school_id'            => now()->timestamp, // dummy unique ID
            'birthday'             => now()->subYears(30),
            'course'               => 'N/A',
            'year'                 => 0,
            'must_change_password' => true,
        ]);

        return redirect()->route('teachers.index')->with('success', 'Teacher added successfully.');
    }

    public function editTeacher($id)
    {
        $teacher = User::findOrFail($id);
        return view('chairperson.teachers.edit', compact('teacher'));
    }

    public function updateTeacher(Request $request, $id)
    {
        $teacher = User::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $teacher->id,
            'role'     => 'required|in:adviser,panelist',
            'password' => 'nullable|string|min:8',
        ]);

        $teacher->name = $request->name;
        $teacher->email = $request->email;
        $teacher->role = $request->role;

        if ($request->filled('password')) {
            $teacher->password = bcrypt($request->password);
        }

        $teacher->save();

        return redirect()->route('teachers.index')->with('success', 'Teacher updated successfully.');
    }

    // ======= SCHEDULES =======

    public function schedules()
    {
        $schedules = Schedule::with('offering')->get();
        return view('chairperson.schedules.index', compact('schedules'));
    }

    // ======= STUDENT IMPORT =======

    public function uploadStudentList(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            Excel::import(new StudentsImport, $request->file('file'));
            return redirect()->back()->with('success', 'Student list imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
