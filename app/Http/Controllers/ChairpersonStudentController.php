<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\AcademicTerm;
use App\Models\Offering;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;

class ChairpersonStudentController extends Controller
{
    private function getActiveTerm()
    {
        return AcademicTerm::where('is_active', true)->first();
    }

    public function index(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        
        //filter students
        $query = Student::query();
        
        if ($activeTerm) {
            //show students
            $query->where('semester', $activeTerm->semester);
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

    public function export(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        
        //filter students
        $query = Student::query();
        
        if ($activeTerm) {
            //show students
            $query->where('semester', $activeTerm->semester);
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

    public function edit($id)
    {
        $student = Student::where('student_id', $id)->firstOrFail();
        return view('chairperson.students.edit', compact('student'));
    }

    public function update(Request $request, $id)
    {
        $student = Student::where('student_id', $id)->firstOrFail();
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email,' . $id . ',student_id',
            'student_id' => 'required|string|unique:students,student_id,' . $id . ',student_id',
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

    public function destroy($id)
    {
        try {
            $student = Student::where('student_id', $id)->firstOrFail();
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

    public function bulkDelete(Request $request)
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
                    $student = Student::where('student_id', $studentId)->firstOrFail();
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

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv|max:10240', // 10MB max
        ], [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'Please upload a CSV file (.csv).',
            'file.max' => 'File size must not exceed 10MB.',
        ]);
        try {
            \Log::info('Starting student import...');
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = number_format($file->getSize() / 1024, 2); // KB
            if ($file->getSize() === 0) {
                return back()->with('error', 'Import failed: The uploaded file is empty. Please check your file and try again.');
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
            $successMessage = "Students imported successfully from '{$fileName}'!";
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
            $errorMessage = "Import failed due to validation errors:\n";
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
            $errorMessage = "Import failed: " . $e->getMessage();
            if (str_contains(strtolower($e->getMessage()), 'duplicate entry')) {
                $errorMessage = "Import failed: Some student IDs or emails already exist in the system. Please check for duplicates.";
            } elseif (str_contains(strtolower($e->getMessage()), 'syntax error')) {
                $errorMessage = "Import failed: The file format is invalid. Please ensure it's a valid Excel or CSV file.";
            } elseif (str_contains(strtolower($e->getMessage()), 'permission denied')) {
                $errorMessage = "Import failed: Permission denied. Please check file permissions.";
            } elseif (str_contains(strtolower($e->getMessage()), 'could not find driver')) {
                $errorMessage = "Import failed: Database connection issue. Please try again.";
            } elseif (str_contains(strtolower($e->getMessage()), 'memory limit')) {
                $errorMessage = "Import failed: File is too large. Please try with a smaller file or contact administrator.";
            }
            return back()->with('error', $errorMessage);
        }
    }
}

