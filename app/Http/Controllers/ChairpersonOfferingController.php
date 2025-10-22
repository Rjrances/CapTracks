<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offering;
use App\Models\User;
use App\Models\AcademicTerm;
use App\Models\Student;

class ChairpersonOfferingController extends Controller
{
    private function getActiveTerm()
    {
        return AcademicTerm::where('is_active', true)->first();
    }

    public function index(Request $request)
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

    public function create()
    {
        $activeTerm = $this->getActiveTerm();
        $teachers = User::whereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator'])
            ->when($activeTerm, function($query) use ($activeTerm) {
                return $query->where('semester', $activeTerm->semester);
            })
            ->orderBy('name')
            ->get();
        $academicTerms = AcademicTerm::notArchived()->get();
        return view('chairperson.offerings.create', compact('teachers', 'academicTerms', 'activeTerm'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'offer_code' => 'required|string|unique:offerings,offer_code',
            'subject_title' => 'required|string|max:255',
            'subject_code' => 'required|string|max:255',
            'faculty_id' => 'required|exists:users,faculty_id',
            'academic_term_id' => 'required|exists:academic_terms,id',
        ], [
            'offer_code.required' => 'Offer code is required (e.g., 11000, 11001, 11002, 11003, 11004).',
            'offer_code.unique' => 'This offer code is already in use.',
            'subject_title.required' => 'Please enter a subject title.',
            'subject_code.required' => 'Subject code is required.',
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
        $message = 'Offering added successfully. Teacher automatically assigned as coordinator.';
        
        return redirect()->route('chairperson.offerings.index')->with('success', $message);
    }

    public function show($id)
    {
        $offering = Offering::with(['teacher', 'academicTerm', 'students'])->where('id', $id)->firstOrFail();
        return view('chairperson.offerings.show', compact('offering'));
    }

    public function edit($id)
    {
        $offering = Offering::with(['teacher', 'academicTerm', 'students'])->where('id', $id)->firstOrFail();
        $teachers = User::whereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator'])
            ->when($offering->academicTerm, function($query) use ($offering) {
                return $query->where('semester', $offering->academicTerm->semester);
            })
            ->orderBy('name')
            ->get();
        $academicTerms = AcademicTerm::notArchived()->get();
        return view('chairperson.offerings.edit', compact('offering', 'teachers', 'academicTerms'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'offer_code' => 'required|string|unique:offerings,offer_code,' . $id,
            'subject_title' => 'required|string|max:255',
            'subject_code' => 'required|string|max:255',
            'faculty_id' => 'required|exists:users,faculty_id',
            'academic_term_id' => 'required|exists:academic_terms,id',
        ], [
            'offer_code.required' => 'Offer code is required (e.g., 11000, 11001, 11002, 11003, 11004).',
            'offer_code.unique' => 'This offer code is already in use.',
            'subject_title.required' => 'Please enter a subject title.',
            'subject_code.required' => 'Subject code is required.',
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
            $newTeacher = User::where('faculty_id', $newTeacherId)->first();
            if ($newTeacher && !$newTeacher->hasRole('coordinator')) {
                $newTeacher->role = 'coordinator';
                $newTeacher->save();
                \Log::info("Automatically assigned coordinator role to teacher {$newTeacher->name} for offering {$offering->subject_code}");
            }
        }
        
        $message = 'Offering updated successfully. Coordinator role assigned automatically.';
            
        return redirect()->route('chairperson.offerings.index')->with('success', $message);
    }

    public function destroy($id)
    {
        $offering = Offering::with('teacher')->where('id', $id)->firstOrFail();
        $teacher = $offering->teacher;
        $offeringCode = $offering->subject_code;
        $offering->delete();
        
        $message = "Offering '{$offeringCode}' deleted successfully.";
        return redirect()->route('chairperson.offerings.index')->with('success', $message);
    }

    public function removeStudent(Request $request, $offeringId, $studentId)
    {
        $offering = Offering::where('id', $offeringId)->firstOrFail();
        $offering->students()->detach($studentId);
        return redirect()->route('chairperson.offerings.show', $offeringId)
            ->with('success', 'Student removed from offering successfully.');
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
        $student = Student::where('student_id', $request->student_id)->firstOrFail();
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
                    $student = Student::where('student_id', $studentId)->firstOrFail();
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
}

