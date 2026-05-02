<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\AcademicTerm;
use App\Services\StudentImportService;

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
        return app(StudentImportService::class)->importFromRequest($request, StudentImportService::MODE_CHAIRPERSON);
    }
}

