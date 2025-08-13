<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offering;
use App\Models\User;
use App\Models\Schedule;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\Role;
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

    /**
     * Get the current active academic term
     */
    private function getActiveTerm()
    {
        return AcademicTerm::where('is_active', true)->first();
    }

    public function indexOfferings(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        $showAllTerms = $request->get('show_all', false);
        
        $offerings = Offering::with(['teacher', 'academicTerm', 'students'])
            ->when($activeTerm && !$showAllTerms, function($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('chairperson.offerings.index', compact('offerings', 'activeTerm', 'showAllTerms'));
    }

    public function createOffering()
    {
        $activeTerm = $this->getActiveTerm();
        $teachers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['teacher', 'adviser', 'panelist']);
        })->get();
        $academicTerms = AcademicTerm::notArchived()->get();
        return view('chairperson.offerings.create', compact('teachers', 'academicTerms', 'activeTerm'));
    }

    public function storeOffering(Request $request)
    {
        $request->validate([
            'subject_title' => 'required|string|max:255',
            'subject_code' => 'required|string|max:100',
            'teacher_id' => 'required|exists:users,id',
            'academic_term_id' => 'required|exists:academic_terms,id',
        ], [
            'subject_title.required' => 'Subject title is required. For Capstone projects, this is typically "Capstone".',
            'subject_code.required' => 'Subject code is required (e.g., CS401, IT401).',
            'teacher_id.required' => 'Please select a teacher for this offering.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
            'academic_term_id.required' => 'Please select an academic term.',
            'academic_term_id.exists' => 'Selected academic term does not exist.',
        ]);

        $data = $request->only('subject_title', 'subject_code', 'teacher_id', 'academic_term_id');
        
        // If no academic term is selected, use the active term
        if (empty($data['academic_term_id'])) {
            $activeTerm = $this->getActiveTerm();
            if ($activeTerm) {
                $data['academic_term_id'] = $activeTerm->id;
            }
        }

        $offering = Offering::create($data);

        // Automatically assign coordinator role to the teacher
        $teacher = User::find($data['teacher_id']);
        if ($teacher) {
            $coordinatorRole = \App\Models\Role::where('name', 'coordinator')->first();
            if ($coordinatorRole && !$teacher->hasRole('coordinator')) {
                $teacher->roles()->attach($coordinatorRole->id);
                \Log::info("Automatically assigned coordinator role to teacher {$teacher->name} for offering {$offering->subject_code}");
            }
        }

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering added successfully. Teacher automatically assigned as coordinator.');
    }

    public function editOffering($id)
    {
        $offering = Offering::with(['teacher', 'academicTerm', 'students'])->findOrFail($id);
        $teachers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['teacher', 'adviser', 'panelist']);
        })->get();
        $academicTerms = AcademicTerm::notArchived()->get();
        $students = Student::all();
        return view('chairperson.offerings.edit', compact('offering', 'teachers', 'academicTerms', 'students'));
    }

    public function updateOffering(Request $request, $id)
    {
        $request->validate([
            'subject_title' => 'required|string|max:255',
            'subject_code'  => 'required|string|max:100',
            'teacher_id' => 'required|exists:users,id',
            'academic_term_id' => 'required|exists:academic_terms,id',
        ], [
            'subject_title.required' => 'Subject title is required. For Capstone projects, this is typically "Capstone".',
            'subject_code.required' => 'Subject code is required (e.g., CS401, IT401).',
            'teacher_id.required' => 'Please select a teacher for this offering.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
            'academic_term_id.required' => 'Please select an academic term.',
            'academic_term_id.exists' => 'Selected academic term does not exist.',
        ]);

        $offering = Offering::findOrFail($id);
        $oldTeacherId = $offering->teacher_id;
        
        $offering->update($request->only('subject_title', 'subject_code', 'teacher_id', 'academic_term_id'));

        // Handle coordinator role assignment for new teacher
        $newTeacherId = $request->input('teacher_id');
        if ($newTeacherId != $oldTeacherId) {
            // Remove coordinator role from old teacher if they have no other offerings
            if ($oldTeacherId) {
                $oldTeacher = User::find($oldTeacherId);
                if ($oldTeacher && $oldTeacher->offerings()->count() === 0) {
                    $coordinatorRole = \App\Models\Role::where('name', 'coordinator')->first();
                    if ($coordinatorRole) {
                        $oldTeacher->roles()->detach($coordinatorRole->id);
                        \Log::info("Removed coordinator role from teacher {$oldTeacher->name} - no more offerings");
                    }
                }
            }

            // Assign coordinator role to new teacher
            $newTeacher = User::find($newTeacherId);
            if ($newTeacher) {
                $coordinatorRole = \App\Models\Role::where('name', 'coordinator')->first();
                if ($coordinatorRole && !$newTeacher->hasRole('coordinator')) {
                    $newTeacher->roles()->attach($coordinatorRole->id);
                    \Log::info("Automatically assigned coordinator role to teacher {$newTeacher->name} for offering {$offering->subject_code}");
                }
            }
        }

        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering updated successfully. Coordinator role updated automatically.');
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

    public function teachers(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        $showAllTerms = $request->get('show_all', false);
        
        $teachers = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['teacher', 'adviser', 'panelist']);
            })
            ->when($activeTerm && $showAllTerms, function($query) use ($activeTerm) {
                // Only filter by offerings when specifically requested to show active term only
                // This allows users to see all faculty by default for easier management
                return $query->whereHas('offerings', function($q) use ($activeTerm) {
                    $q->where('academic_term_id', $activeTerm->id);
                });
            })
            ->orderBy('name')
            ->get();
            
        return view('chairperson.teachers.index', compact('teachers', 'activeTerm', 'showAllTerms'));
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

        $user = User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'password'             => bcrypt($request->password),
            'school_id'            => now()->timestamp, // dummy unique ID
            'birthday'             => now()->subYears(30),
            'department'           => 'N/A',
            'role'                 => $request->role,
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



    // ======= STUDENT MANAGEMENT =======

    public function indexStudents(Request $request)
    {
        $query = Student::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('course', 'like', "%{$search}%");
            });
        }
        
        // Filter by course
        if ($request->filled('course')) {
            $query->where('course', $request->get('course'));
        }
        
        // Filter by semester
        if ($request->filled('semester')) {
            $query->where('semester', $request->get('semester'));
        }
        
        // Get unique courses and semesters for filters
        $courses = Student::distinct()->pluck('course')->sort();
        $semesters = Student::distinct()->pluck('semester')->sort();
        
        $students = $query->with(['offerings', 'groups'])
            ->orderBy('name')
            ->paginate(20);
            
        return view('chairperson.students.index', compact('students', 'courses', 'semesters'));
    }

    public function exportStudents(Request $request)
    {
        $query = Student::query();
        
        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('course', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('course')) {
            $query->where('course', $request->get('course'));
        }
        
        if ($request->filled('semester')) {
            $query->where('semester', $request->get('semester'));
        }
        
        $students = $query->with(['offerings', 'groups'])->get();
        
        $filename = 'students_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, ['Student ID', 'Name', 'Email', 'Course', 'Semester', 'Enrolled Offerings', 'Group Status']);
            
            // CSV data
            foreach ($students as $student) {
                $enrolledOfferings = $student->offerings->pluck('subject_title')->implode(', ');
                $groupStatus = $student->groups->count() > 0 ? 'In Group: ' . $student->groups->first()->name : 'No Group';
                
                fputcsv($file, [
                    $student->student_id,
                    $student->name,
                    $student->email,
                    $student->course,
                    $student->semester,
                    $enrolledOfferings ?: 'Not Enrolled',
                    $groupStatus
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    // ======= STUDENT IMPORT =======

    public function uploadStudentList(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ], [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'Please upload an Excel file (.xlsx, .xls) or CSV file (.csv).',
            'file.max' => 'File size must not exceed 10MB.',
        ]);

        try {
            // Add debugging
            \Log::info('Starting student import...');
            
            // Get file info for better feedback
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = number_format($file->getSize() / 1024, 2); // KB
            
            // Check if file is empty
            if ($file->getSize() === 0) {
                return back()->with('error', '❌ Import failed: The uploaded file is empty. Please check your file and try again.');
            }
            
            \Log::info("Importing file: {$fileName} (Size: {$fileSize} KB)");
            
            Excel::import(new StudentsImport, $file);
            
            \Log::info('Student import completed successfully');
            
            // Get count of imported students (this is approximate since we don't track individual imports)
            $successMessage = "✅ Students imported successfully from '{$fileName}'!";
            
            // Check if there's an offering_id parameter to redirect to
            if ($request->has('offering_id')) {
                $offeringId = $request->get('offering_id');
                return redirect()->route('chairperson.offerings.show', $offeringId)
                    ->with('success', $successMessage . ' You can now enroll them in this offering.');
            }
            
            // If no offering_id, redirect back to import page
            return back()->with('success', $successMessage);
            
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Log::error('Student import validation failed: ' . $e->getMessage());
            
            $errorMessage = "❌ Import failed due to validation errors:\n";
            
            // Collect all validation errors from failures
            $allErrors = [];
            foreach ($e->failures() as $failure) {
                foreach ($failure->errors() as $error) {
                    $allErrors[] = $error;
                }
            }
            
            $errorMessage .= "• " . implode("\n• ", $allErrors);
            
            return back()->with('error', $errorMessage);
            
        } catch (\Exception $e) {
            \Log::error('Student import failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            $errorMessage = "❌ Import failed: " . $e->getMessage();
            
            // Provide more user-friendly error messages for common issues
            if (str_contains(strtolower($e->getMessage()), 'duplicate entry')) {
                $errorMessage = "❌ Import failed: Some student IDs or emails already exist in the system. Please check for duplicates.";
            } elseif (str_contains(strtolower($e->getMessage()), 'syntax error')) {
                $errorMessage = "❌ Import failed: The file format is invalid. Please ensure it's a valid Excel or CSV file.";
            } elseif (str_contains(strtolower($e->getMessage()), 'permission denied')) {
                $errorMessage = "❌ Import failed: Permission denied. Please check file permissions.";
            } elseif (str_contains(strtolower($e->getMessage()), 'could not find driver')) {
                $errorMessage = "❌ Import failed: Database connection issue. Please try again.";
            } elseif (str_contains(strtolower($e->getMessage()), 'memory limit')) {
                $errorMessage = "❌ Import failed: File is too large. Please try with a smaller file or contact administrator.";
            }
            
            return back()->with('error', $errorMessage);
        }
    }

    // ======= FACULTY MANAGEMENT =======

    public function facultyManagement()
    {
        $faculty = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['teacher', 'adviser', 'panelist']);
        })->with('roles')->get();
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
            'school_id' => [
                'required',
                'string',
                'unique:users,school_id',
                'regex:/^\d{5}$/', // Must be exactly 5 digits
            ],
            'department' => 'nullable|string|max:255',
        ], [
            'school_id.regex' => 'Faculty/Staff ID must be exactly 5 digits (e.g., 12345)',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'school_id' => $request->school_id,
            'department' => $request->department,
            'role' => 'teacher',
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
            'department' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->department,
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
