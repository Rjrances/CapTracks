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
        $teachers = User::whereIn('role', ['teacher', 'adviser', 'panelist'])->get();
        $academicTerms = AcademicTerm::notArchived()->get();
        return view('chairperson.offerings.create', compact('teachers', 'academicTerms', 'activeTerm'));
    }
    public function storeOffering(Request $request)
    {
        $request->validate([
            'offer_code' => 'required|string|unique:offerings,offer_code',
            'subject_title' => 'required|in:Capstone Project I,Capstone Project II,Thesis I,Thesis II',
            'subject_code' => 'required|in:CS-CAP-401,CS-CAP-402,CS-THS-301,CS-THS-302',
            'faculty_id' => 'required|exists:users,faculty_id',
            'academic_term_id' => 'required|exists:academic_terms,id',
        ], [
            'offer_code.required' => 'Offer code is required (e.g., 11000, 11001, 11002, 11003, 11004).',
            'offer_code.unique' => 'This offer code is already in use.',
            'subject_title.required' => 'Please select a subject title from the dropdown.',
            'subject_title.in' => 'Please select a valid subject title from the dropdown.',
            'subject_code.required' => 'Subject code is required (CS-CAP-401, CS-CAP-402, CS-THS-301, CS-THS-302).',
            'subject_code.in' => 'Subject code must be one of: CS-CAP-401, CS-CAP-402, CS-THS-301, CS-THS-302.',
            'faculty_id.required' => 'Please select a teacher for this offering.',
            'faculty_id.exists' => 'Selected teacher does not exist.',
            'academic_term_id.required' => 'Please select an academic term.',
            'academic_term_id.exists' => 'Selected academic term does not exist.',
        ]);
        $data = $request->only('offer_code', 'subject_title', 'subject_code', 'faculty_id', 'academic_term_id');
        if (empty($data['academic_term_id'])) {
            $activeTerm = $this->getActiveTerm();
            if ($activeTerm) {
                $data['academic_term_id'] = $activeTerm->id;
            }
        }
        $offering = Offering::create($data);
        $teacher = User::where('faculty_id', $data['faculty_id'])->first();
        if ($teacher && !$teacher->hasRole('coordinator')) {
            $teacher->role = 'coordinator';
            $teacher->save();
            \Log::info("Automatically assigned coordinator role to teacher {$teacher->name} for offering {$offering->subject_code}");
        }
        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering added successfully. Teacher automatically assigned as coordinator.');
    }
    public function editOffering($id)
    {
        $offering = Offering::with(['teacher', 'academicTerm', 'students'])->where('id', $id)->firstOrFail();
        $teachers = User::whereIn('role', ['teacher', 'adviser', 'panelist'])->get();
        $academicTerms = AcademicTerm::notArchived()->get();
        return view('chairperson.offerings.edit', compact('offering', 'teachers', 'academicTerms'));
    }
    public function updateOffering(Request $request, $id)
    {
        $request->validate([
            'offer_code' => 'required|string|unique:offerings,offer_code,' . $id,
            'subject_title' => 'required|in:Capstone Project I,Capstone Project II,Thesis I,Thesis II',
            'subject_code' => 'required|in:CS-CAP-401,CS-CAP-402,CS-THS-301,CS-THS-302',
            'faculty_id' => 'required|exists:users,faculty_id',
            'academic_term_id' => 'required|exists:academic_terms,id',
        ], [
            'offer_code.required' => 'Offer code is required (e.g., 11000, 11001, 11002, 11003, 11004).',
            'offer_code.unique' => 'This offer code is already in use.',
            'subject_title.required' => 'Please select a subject title from the dropdown.',
            'subject_title.in' => 'Please select a valid subject title from the dropdown.',
            'subject_code.required' => 'Subject code is required (CS-CAP-401, CS-CAP-402, CS-THS-301, CS-THS-302).',
            'subject_code.in' => 'Subject code must be one of: CS-CAP-401, CS-CAP-402, CS-THS-301, CS-THS-302.',
            'faculty_id.required' => 'Please select a teacher for this offering.',
            'faculty_id.exists' => 'Selected teacher does not exist.',
            'academic_term_id.required' => 'Please select an academic term.',
            'academic_term_id.exists' => 'Selected academic term does not exist.',
        ]);
        $offering = Offering::where('id', $id)->firstOrFail();
        $oldTeacherId = $offering->faculty_id;
        $offering->update($request->only('offer_code', 'subject_title', 'subject_code', 'faculty_id', 'academic_term_id'));
        $newTeacherId = $request->input('faculty_id');
        if ($newTeacherId != $oldTeacherId) {
            if ($oldTeacherId) {
                $oldTeacher = User::where('faculty_id', $oldTeacherId)->first();
                if ($oldTeacher && $oldTeacher->offerings()->count() === 0) {
                    $oldTeacher->role = 'teacher';
                    $oldTeacher->save();
                    \Log::info("Removed coordinator role from teacher {$oldTeacher->name} - no more offerings");
                }
            }
            $newTeacher = User::where('faculty_id', $newTeacherId)->first();
            if ($newTeacher && !$newTeacher->hasRole('coordinator')) {
                $newTeacher->role = 'coordinator';
                $newTeacher->save();
                \Log::info("Automatically assigned coordinator role to teacher {$newTeacher->name} for offering {$offering->subject_code}");
            }
        }
        return redirect()->route('chairperson.offerings.index')->with('success', 'Offering updated successfully. Coordinator role updated automatically.');
    }
    public function deleteOffering($id)
    {
        $offering = Offering::with('teacher')->where('id', $id)->firstOrFail();
        $teacher = $offering->teacher;
        $offeringCode = $offering->subject_code;
        $offering->delete();
        if ($teacher && $teacher->hasRole('coordinator')) {
            \DB::afterCommit(function() use ($teacher) {
                $this->manageTeacherRoleTransition($teacher);
            });
        }
        $message = "Offering '{$offeringCode}' deleted successfully.";
        if ($teacher) {
            $message .= " Teacher role will be updated if needed.";
        }
        return redirect()->route('chairperson.offerings.index')->with('success', $message);
    }
    public function forceUpdateAllRoles()
    {
        try {
            $usersUpdated = 0;
            $users = User::whereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator'])->get();
            foreach ($users as $user) {
                if ($user->updateRoleBasedOnOfferings()) {
                    $usersUpdated++;
                }
            }
            $message = "Role consistency check completed. {$usersUpdated} users had their roles updated.";
            \Log::info($message);
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error updating all user roles: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating user roles. Please check logs.');
        }
    }
    public function forceUpdateUserRole($userId)
    {
        try {
            $user = User::where('faculty_id', $userId)->firstOrFail();
            $oldRole = $user->role;
            $roleChanged = $user->updateRoleBasedOnOfferings();
            if ($roleChanged) {
                $message = "User {$user->name} role updated from '{$oldRole}' to '{$user->role}'";
                \Log::info($message);
                return redirect()->back()->with('success', $message);
            } else {
                $message = "User {$user->name} role is already correct: '{$user->role}'";
                return redirect()->back()->with('info', $message);
            }
        } catch (\Exception $e) {
            \Log::error('Error updating user role: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating user role. Please check logs.');
        }
    }
    public function fixRoleInconsistencies()
    {
        try {
            $usersUpdated = 0;
            $users = User::whereIn('role', ['teacher', 'coordinator', 'adviser', 'panelist'])->get();
            foreach ($users as $user) {
                if ($user->updateRoleBasedOnOfferings()) {
                    $usersUpdated++;
                }
            }
            $message = "Role consistency check completed. {$usersUpdated} users had their roles updated.";
            \Log::info($message);
            return redirect()->route('chairperson.offerings.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error fixing role inconsistencies: ' . $e->getMessage());
            return redirect()->route('chairperson.offerings.index')->with('error', 'Error fixing role inconsistencies. Please check logs.');
        }
    }
    private function manageTeacherRoleTransition(User $teacher)
    {
        try {
            $teacher->refresh();
            $roleChanged = $teacher->updateRoleBasedOnOfferings();
            if ($roleChanged) {
                \Log::info("Role transition completed for teacher {$teacher->name} (ID: {$teacher->id})");
            } else {
                \Log::info("No role transition needed for teacher {$teacher->name} (ID: {$teacher->id})");
            }
        } catch (\Exception $e) {
            \Log::error("Error managing role transition for teacher {$teacher->id}: " . $e->getMessage());
        }
    }
    public function showOffering($id)
    {
        $offering = Offering::with(['teacher', 'academicTerm', 'students'])->where('id', $id)->firstOrFail();
        return view('chairperson.offerings.show', compact('offering'));
    }
    public function removeStudentFromOffering(Request $request, $offeringId, $studentId)
    {
        $offering = Offering::where('id', $offeringId)->firstOrFail();
        $offering->students()->detach($studentId);
        return redirect()->route('chairperson.offerings.show', $offeringId)
            ->with('success', 'Student removed from offering successfully.');
    }
    public function teachers(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        $showAllTerms = $request->get('show_all', false);
        $sortBy = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        
        $teachers = User::query()
            ->when($showAllTerms, function($query) {
                return $query->whereNotIn('role', ['student']);
            }, function($query) use ($activeTerm) {
                return $query->whereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator'])
                    ->whereHas('offerings', function($q) use ($activeTerm) {
                        $q->where('academic_term_id', $activeTerm->id);
                    });
            })
            ->orderBy($sortBy, $sortDirection)
            ->get();
        return view('chairperson.teachers.index', compact('teachers', 'activeTerm', 'showAllTerms', 'sortBy', 'sortDirection'));
    }
    public function createTeacher()
    {
        return view('chairperson.teachers.create');
    }
    public function storeTeacher(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email|unique:user_accounts,email',
            'faculty_id' => 'required|string|max:20|unique:users,faculty_id|unique:user_accounts,faculty_id',
            'role'     => 'required|in:adviser,panelist',
            'password' => 'required|string|min:8',
        ]);
        // Use faculty_id from form input
        $facultyId = $request->faculty_id;
        
        $user = User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'birthday'             => now()->subYears(30),
            'department'           => 'N/A',
            'role'                 => $request->role,
            'faculty_id'           => $facultyId,
        ]);

        // Create faculty account
        \App\Models\UserAccount::create([
            'faculty_id' => $facultyId,
            'user_id' => $user->id,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return redirect()->route('teachers.index')->with('success', 'Teacher added successfully.');
    }
    public function editTeacher($id)
    {
        $teacher = User::where('faculty_id', $id)->firstOrFail();
        return view('chairperson.teachers.edit', compact('teacher'));
    }
    public function updateTeacher(Request $request, $id)
    {
        $teacher = User::where('faculty_id', $id)->firstOrFail();
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
    public function indexStudents(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        
        // Filter students by active term through their offerings
        $query = Student::query();
        
        if ($activeTerm) {
            // Show students who are either enrolled in active term offerings OR have no enrollments yet
            $query->where(function($q) use ($activeTerm) {
                $q->whereHas('offerings', function($subQ) use ($activeTerm) {
                    $subQ->where('academic_term_id', $activeTerm->id);
                })->orWhereDoesntHave('offerings');
            });
        }
        
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
        
        $sortBy = $request->get('sort_by', 'student_id');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSortFields = ['student_id', 'name', 'email', 'course'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'student_id';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }
        $query->orderBy($sortBy, $sortOrder);
        
        $courses = Student::distinct()->pluck('course')->sort();
        $students = $query->with(['offerings', 'groups'])->paginate(20);
        $students->appends($request->all());
        
        return view('chairperson.students.index', compact('students', 'courses', 'activeTerm', 'sortBy', 'sortOrder'));
    }
    public function exportStudents(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        
        // Filter students by active term through their offerings
        $query = Student::query();
        
        if ($activeTerm) {
            // Show students who are either enrolled in active term offerings OR have no enrollments yet
            $query->where(function($q) use ($activeTerm) {
                $q->whereHas('offerings', function($subQ) use ($activeTerm) {
                    $subQ->where('academic_term_id', $activeTerm->id);
                })->orWhereDoesntHave('offerings');
            });
        }
        
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
        
        $sortBy = $request->get('sort_by', 'student_id');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSortFields = ['student_id', 'name', 'email', 'course'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'student_id';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }
        $query->orderBy($sortBy, $sortOrder);
        $students = $query->with(['offerings', 'groups'])->get();
        $filename = 'students_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Student ID', 'Name', 'Email', 'Course', 'Enrolled Offerings', 'Group Status']);
            foreach ($students as $student) {
                $enrolledOfferings = $student->offerings->pluck('subject_title')->implode(', ');
                $groupStatus = $student->groups->count() > 0 ? 'In Group: ' . $student->groups->first()->name : 'No Group';
                fputcsv($file, [
                    $student->student_id,
                    $student->name,
                    $student->email,
                    $student->course,
                    $enrolledOfferings ?: 'Not Enrolled',
                    $groupStatus
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
    public function deleteStudent($id)
    {
        try {
            $student = Student::findOrFail($id);
            $studentName = $student->name;
            $student->delete();
            return redirect()->route('chairperson.students.index')
                ->with('success', "Student '{$studentName}' has been deleted successfully.");
        } catch (\Exception $e) {
            \Log::error('Error deleting student: ' . $e->getMessage());
            return redirect()->route('chairperson.students.index')
                ->with('error', 'Failed to delete student. Please try again.');
        }
    }
    public function editStudent($id)
    {
        $student = Student::findOrFail($id);
        return view('chairperson.students.edit', compact('student'));
    }
    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email,student_id,' . $id,
            'student_id' => 'required|string|unique:students,student_id,' . $id,
            'course' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',
        ]);
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'student_id' => $request->student_id,
            'course' => $request->course,
            'semester' => $request->semester,
        ];
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }
        $student->update($updateData);
        return redirect()->route('chairperson.students.index')->with('success', 'Student updated successfully.');
    }
    public function showUnenrolledStudents($offeringId)
    {
        $offering = Offering::where('id', $offeringId)->firstOrFail();
        $unenrolledStudents = Student::whereDoesntHave('offerings')
            ->orderBy('name')
            ->get();
        return view('chairperson.offerings.unenrolled-students', compact('offering', 'unenrolledStudents'));
    }
    public function enrollStudent(Request $request, $offeringId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
        ]);
        $offering = Offering::where('id', $offeringId)->firstOrFail();
        $student = Student::findOrFail($request->student_id);
        $student->enrollInOffering($offering);
        return redirect()->route('chairperson.offerings.show', $offeringId)
            ->with('success', "Student '{$student->name}' has been enrolled in {$offering->subject_code}.");
    }
    public function enrollMultipleStudents(Request $request, $offeringId)
    {
        try {
            $studentIds = $request->input('student_ids');
            if (is_string($studentIds)) {
                $studentIds = json_decode($studentIds, true);
            }
            if (empty($studentIds) || !is_array($studentIds)) {
                return redirect()->route('chairperson.offerings.show', $offeringId)
                    ->with('error', 'No students selected for enrollment.');
            }
            $offering = Offering::where('id', $offeringId)->firstOrFail();
            $enrolledCount = 0;
            $enrolledNames = [];
            $errors = [];
            foreach ($studentIds as $studentId) {
                try {
                    $student = Student::findOrFail($studentId);
                    $student->enrollInOffering($offering);
                    $enrolledCount++;
                    $enrolledNames[] = $student->name;
                    \Log::info("Student {$student->name} (ID: {$studentId}) enrolled in offering {$offering->subject_code}");
                } catch (\Exception $e) {
                    $errors[] = "Student ID {$studentId}: " . $e->getMessage();
                    \Log::error("Error enrolling student ID {$studentId}: " . $e->getMessage());
                }
            }
            $message = "Successfully enrolled {$enrolledCount} student(s): " . implode(', ', $enrolledNames);
            if (!empty($errors)) {
                $message .= "\nErrors occurred with some students: " . implode('; ', $errors);
                return redirect()->route('chairperson.offerings.show', $offeringId)
                    ->with('warning', $message);
            }
            return redirect()->route('chairperson.offerings.show', $offeringId)
                ->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error in bulk enrollment: ' . $e->getMessage());
            return redirect()->route('chairperson.offerings.show', $offeringId)
                ->with('error', 'Error during bulk enrollment. Please try again.');
        }
    }
    public function bulkDeleteStudents(Request $request)
    {
        try {
            $studentIds = json_decode($request->input('student_ids'), true);
            if (empty($studentIds) || !is_array($studentIds)) {
                return redirect()->route('chairperson.students.index')->with('error', 'No students selected for deletion.');
            }
            $deletedCount = 0;
            $deletedNames = [];
            $errors = [];
            foreach ($studentIds as $studentId) {
                try {
                    $student = Student::findOrFail($studentId);
                    $studentName = $student->name;
                    $student->delete();
                    $deletedCount++;
                    $deletedNames[] = $studentName;
                    \Log::info("Student {$studentName} (ID: {$studentId}) bulk deleted by chairperson");
                } catch (\Exception $e) {
                    $errors[] = "Student ID {$studentId}: " . $e->getMessage();
                    \Log::error("Error bulk deleting student ID {$studentId}: " . $e->getMessage());
                }
            }
            $message = "Successfully deleted {$deletedCount} student(s): " . implode(', ', $deletedNames);
            if (!empty($errors)) {
                $message .= "\nErrors occurred with some students: " . implode('; ', $errors);
                return redirect()->route('chairperson.students.index')->with('warning', $message);
            }
            return redirect()->route('chairperson.students.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error in bulk delete: ' . $e->getMessage());
            return redirect()->route('chairperson.students.index')->with('error', 'Error during bulk deletion. Please try again.');
        }
    }
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
            \Log::info('Starting student import...');
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = number_format($file->getSize() / 1024, 2); // KB
            if ($file->getSize() === 0) {
                return back()->with('error', '❌ Import failed: The uploaded file is empty. Please check your file and try again.');
            }
            \Log::info("Importing file: {$fileName} (Size: {$fileSize} KB)");
            $offeringId = $request->get('offering_id');
            \Log::info("Importing students with offering_id: " . ($offeringId ?: 'none'));
            $import = new StudentsImport($offeringId);
            Excel::import($import, $file);
            \Log::info('Student import completed successfully');
            if ($offeringId) {
                try {
                    $offering = \App\Models\Offering::find($offeringId);
                    if ($offering) {
                        $recentStudents = \App\Models\Student::where('created_at', '>=', now()->subMinutes(2))->get();
                        if ($recentStudents->count() > 0) {
                            foreach ($recentStudents as $student) {
                                $student->enrollInOffering($offering);
                            }
                            \Log::info("Fallback enrollment: Enrolled {$recentStudents->count()} students in offering {$offering->subject_code} (single enrollment)");
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Fallback enrollment failed: ' . $e->getMessage());
                }
            }
            $successMessage = "✅ Students imported successfully from '{$fileName}'!";
            if ($request->has('offering_id')) {
                $offeringId = $request->get('offering_id');
                $offering = \App\Models\Offering::find($offeringId);
                if ($offering) {
                    $enrolledCount = $offering->students()->count();
                    \Log::info("Offering {$offering->subject_code} now has {$enrolledCount} enrolled students");
                    $enrollmentMessage = " Students have been automatically enrolled in {$offering->subject_code}.";
                } else {
                    $enrollmentMessage = "";
                }
                return redirect()->route('chairperson.offerings.show', $offeringId)
                    ->with('success', $successMessage . $enrollmentMessage);
            }
            return back()->with('success', $successMessage);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Log::error('Student import validation failed: ' . $e->getMessage());
            $errorMessage = "❌ Import failed due to validation errors:\n";
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
    public function facultyManagement()
    {
        $faculty = User::whereIn('role', ['teacher', 'adviser', 'panelist'])->get();
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
            'email' => 'required|email|unique:users,email|unique:user_accounts,email',
            'faculty_id' => 'required|string|max:20|unique:users,faculty_id|unique:user_accounts,faculty_id',
            'department' => 'nullable|string|max:255',
        ]);
        // Use faculty_id from form input
        $facultyId = $request->faculty_id;
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->department,
            'role' => 'teacher',
            'faculty_id' => $facultyId,
        ]);

        // Create faculty account
        \App\Models\UserAccount::create([
            'faculty_id' => $facultyId,
            'user_id' => $user->id,
            'email' => $request->email,
            'password' => bcrypt('password123'),
        ]);

        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member added successfully!');
    }
    public function editFaculty($id)
    {
        $teacher = User::where('faculty_id', $id)->firstOrFail();
        return view('chairperson.teachers.edit', compact('teacher'));
    }
    public function updateFaculty(Request $request, $id)
    {
        $faculty = User::where('faculty_id', $id)->firstOrFail();
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $faculty->id,
            'department' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
        ]);
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->department,
        ];
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
            $updateData['must_change_password'] = false;
        }
        $faculty->update($updateData);
        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member updated successfully.');
    }
    public function deleteFaculty($id)
    {
        $faculty = User::where('faculty_id', $id)->firstOrFail();
        $faculty->delete();
        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member deleted successfully.');
    }
}
