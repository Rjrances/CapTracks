<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offering;
use App\Models\User;
use App\Models\Schedule;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\FacultyImport;

class ChairpersonController extends Controller
{
    public function index()
    {
        return view('chairperson.dashboard');
    }

    // ======= OFFERINGS =======

    public function indexOfferings()
    {
        $offerings = Offering::with(['teacher', 'academicTerm', 'students'])
            ->orderBy('created_at', 'desc')
            ->get();
        return view('chairperson.offerings.index', compact('offerings'));
    }

    public function createOffering()
    {
        $teachers = User::whereIn('role', ['adviser', 'panelist'])->get();
        $academicTerms = \App\Models\AcademicTerm::notArchived()->get();
        return view('chairperson.offerings.create', compact('teachers', 'academicTerms'));
    }

    public function storeOffering(Request $request)
    {
        $request->validate([
            'subject_title' => 'required|string|max:255',
            'subject_code' => 'required|string|max:100',
            'teacher_id' => 'required|exists:users,id',
            'academic_term_id' => 'required|exists:academic_terms,id',
        ]);

        Offering::create($request->only('subject_title', 'subject_code', 'teacher_id', 'academic_term_id'));

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering added successfully.');
    }

    public function editOffering($id)
    {
        $offering = Offering::with(['teacher', 'academicTerm', 'students'])->findOrFail($id);
        $teachers = User::whereIn('role', ['adviser', 'panelist'])->get();
        $academicTerms = \App\Models\AcademicTerm::notArchived()->get();
        $students = \App\Models\Student::all();
        return view('chairperson.offerings.edit', compact('offering', 'teachers', 'academicTerms', 'students'));
    }

    public function updateOffering(Request $request, $id)
    {
        $request->validate([
            'subject_title' => 'required|string|max:255',
            'subject_code'  => 'required|string|max:100',
            'teacher_id' => 'required|exists:users,id',
            'academic_term_id' => 'required|exists:academic_terms,id',
        ]);

        $offering = Offering::findOrFail($id);
        $offering->update($request->only('subject_title', 'subject_code', 'teacher_id', 'academic_term_id'));

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering updated successfully.');
    }

    public function deleteOffering($id)
    {
        $offering = Offering::findOrFail($id);
        $offering->delete();

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering deleted.');
    }

    // ======= OFFERING STUDENT MANAGEMENT =======

    public function showOffering($id)
    {
        $offering = Offering::with(['teacher', 'academicTerm', 'students'])->findOrFail($id);
        $availableStudents = \App\Models\Student::whereDoesntHave('offerings', function($query) use ($id) {
            $query->where('offering_id', $id);
        })->get();
        
        return view('chairperson.offerings.show', compact('offering', 'availableStudents'));
    }

    public function addStudentsToOffering(Request $request, $id)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        $offering = Offering::findOrFail($id);
        $offering->students()->attach($request->student_ids);

        return redirect()->route('chairperson.offerings.show', $id)
            ->with('success', count($request->student_ids) . ' student(s) added to offering successfully.');
    }

    public function removeStudentFromOffering(Request $request, $offeringId, $studentId)
    {
        $offering = Offering::findOrFail($offeringId);
        $offering->students()->detach($studentId);

        return redirect()->route('chairperson.offerings.show', $offeringId)
            ->with('success', 'Student removed from offering successfully.');
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



    // ======= STUDENT IMPORT =======

    public function uploadStudentList(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new StudentsImport, $request->file('file'));
            return back()->with('success', 'Students imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing students: ' . $e->getMessage());
        }
    }

    // ======= FACULTY MANAGEMENT =======

    public function facultyManagement()
    {
        $faculty = User::whereIn('role', ['adviser', 'panelist'])->get();
        return view('chairperson.teachers.index', compact('faculty'));
    }

    public function uploadFacultyList(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new FacultyImport, $request->file('file'));
            return back()->with('success', 'Faculty imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing faculty: ' . $e->getMessage());
        }
    }

    public function createFaculty()
    {
        return view('chairperson.teachers.create');
    }

    public function createFacultyManual()
    {
        return view('chairperson.teachers.create-manual');
    }

    public function storeFaculty(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new FacultyImport, $request->file('file'));
            return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty members imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing faculty: ' . $e->getMessage());
        }
    }

    public function storeFacultyManual(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'school_id' => 'required|string|unique:users,school_id',
            'role' => 'required|in:adviser,panelist',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'school_id' => $request->school_id,
            'role' => $request->role,
            'department' => $request->department,
            'position' => $request->position,
            'password' => bcrypt('password123'),
            'must_change_password' => true,
        ]);

        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member added successfully!');
    }

    public function editFaculty($id)
    {
        $teacher = User::findOrFail($id);
        return view('chairperson.teachers.edit', compact('teacher'));
    }

    public function updateFaculty(Request $request, $id)
    {
        $faculty = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:adviser,panelist',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'department' => $request->department,
            'position' => $request->position,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
            $updateData['must_change_password'] = false;
        }

        $faculty->update($updateData);

        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member updated successfully.');
    }

    public function deleteFaculty($id)
    {
        $faculty = User::findOrFail($id);
        $faculty->delete();

        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member deleted successfully.');
    }
}
