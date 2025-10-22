<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AcademicTerm;
use App\Imports\FacultyImport;
use Maatwebsite\Excel\Facades\Excel;

class ChairpersonFacultyController extends Controller
{
  
    private function getActiveTerm()
    {
        return AcademicTerm::where('is_active', true)->first();
    }

    //show faculty
    public function index(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        $sortBy = $request->get('sort', 'faculty_id');
        $sortDirection = $request->get('direction', 'asc');
        
        $query = User::query()
            ->with('roles')
            ->whereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator', 'chairperson']);
        
        if ($activeTerm) {
            $query->where('semester', $activeTerm->semester);
        }
        
        $teachers = $query->orderBy($sortBy, $sortDirection)
            ->paginate(20);
            
        return view('chairperson.teachers.index', compact('teachers', 'activeTerm', 'sortBy', 'sortDirection'));
    }

    public function create()
    {
        return view('chairperson.teachers.create');
    }

    public function createManual()
    {
        return view('chairperson.teachers.create-manual');
    }

    public function store(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'faculty_id' => 'required|string|max:20',
            'role'     => 'required|in:adviser,panelist',
            'password' => 'required|string|min:8',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'faculty_id.required' => 'Faculty ID is required.',
            'faculty_id.string' => 'Faculty ID must be a string.',
            'faculty_id.max' => 'Faculty ID must not exceed 20 characters.',
            'role.required' => 'Role is required.',
            'role.in' => 'Role must be either adviser or panelist.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);
        
        
        if ($activeTerm) {
            $existingEmail = User::where('email', $request->email)
                ->where('semester', $activeTerm->semester)
                ->first();
            
            if ($existingEmail) {
                return back()->withErrors(['email' => 'The email has already been taken by another user in this semester.']);
            }
            
            $existingFacultyId = User::where('faculty_id', $request->faculty_id)
                ->where('semester', $activeTerm->semester)
                ->first();
            
            if ($existingFacultyId) {
                return back()->withErrors(['faculty_id' => 'The faculty ID has already been taken by another user in this semester.']);
            }
        }
        
        $facultyId = $request->faculty_id;
        
        $user = User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'birthday'             => now()->subYears(30),
            'department'           => 'N/A',
            'role'                 => $request->role,
            'faculty_id'           => $facultyId,
            'semester'             => $activeTerm ? $activeTerm->semester : 'Unknown',
        ]);

        //create account
        \App\Models\UserAccount::create([
            'faculty_id' => $facultyId,
            'user_id' => $user->id,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return redirect()->route('teachers.index')->with('success', 'Teacher added successfully.');
    }

    public function storeManual(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'faculty_id' => 'required|string|max:20',
            'department' => 'nullable|string|max:255',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'faculty_id.required' => 'Faculty ID is required.',
            'faculty_id.string' => 'Faculty ID must be a string.',
            'faculty_id.max' => 'Faculty ID must not exceed 20 characters.',
        ]);
        
        //email validation
        if ($activeTerm) {
            $existingEmail = User::where('email', $request->email)
                ->where('semester', $activeTerm->semester)
                ->first();
            
            if ($existingEmail) {
                return back()->withErrors(['email' => 'The email has already been taken by another user in this semester.']);
            }
            
            $existingFacultyId = User::where('faculty_id', $request->faculty_id)
                ->where('semester', $activeTerm->semester)
                ->first();
            
            if ($existingFacultyId) {
                return back()->withErrors(['faculty_id' => 'The faculty ID has already been taken by another user in this semester.']);
            }
        }
        
        $facultyId = $request->faculty_id;
        
        //create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'department' => $request->department,
            'role' => 'teacher',
            'faculty_id' => $facultyId,
            'semester' => $activeTerm ? $activeTerm->semester : 'Unknown',
        ]);

        //create account
        \App\Models\UserAccount::create([
            'faculty_id' => $facultyId,
            'user_id' => $user->id,
            'email' => $request->email,
            'password' => bcrypt('password123'),
        ]);

        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member added successfully!');
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
            \Log::info('Starting faculty import...');
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = number_format($file->getSize() / 1024, 2); // KB
            
            if ($file->getSize() === 0) {
                return back()->with('error', 'Import failed: The uploaded file is empty. Please check your file and try again.');
            }
            
            \Log::info("Importing file: {$fileName} (Size: {$fileSize} KB)");
            $activeTerm = $this->getActiveTerm();
            $semester = $activeTerm ? $activeTerm->semester : 'Unknown';
            Excel::import(new FacultyImport($semester), $file);
            \Log::info('Faculty import completed successfully');
            
            $successMessage = "Faculty members imported successfully from '{$fileName}'!";
            return redirect()->route('chairperson.teachers.index')->with('success', $successMessage);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Log::error('Faculty import validation failed: ' . $e->getMessage());
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
            \Log::error('Faculty import failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            $errorMessage = "Import failed: " . $e->getMessage();
            if (str_contains(strtolower($e->getMessage()), 'duplicate entry')) {
                $errorMessage = "Import failed: Some faculty IDs or emails already exist in the system. Please check for duplicates.";
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

    public function edit($id)
    {
        $activeTerm = $this->getActiveTerm();
        $query = User::where('faculty_id', $id);
        
        if ($activeTerm) {
            $query->where('semester', $activeTerm->semester);
        }
        
        $teacher = $query->firstOrFail();
        return view('chairperson.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, $id)
    {
        $activeTerm = $this->getActiveTerm();
        $query = User::where('faculty_id', $id);
        
        if ($activeTerm) {
            $query->where('semester', $activeTerm->semester);
        }
        
        $faculty = $query->firstOrFail();
        
        //email validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) use ($faculty) {
                    $existingUser = User::where('email', $value)
                        ->where('id', '!=', $faculty->id)
                        ->where('semester', $faculty->semester)
                        ->first();
                    
                    if ($existingUser) {
                        $fail('The email has already been taken by another user in this semester.');
                    }
                }
            ],
            'role' => 'required|in:teacher,adviser,panelist,coordinator,chairperson',
            'department' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
        ]);
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'department' => $request->department,
        ];
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
            $updateData['must_change_password'] = false;
        }
        $faculty->update($updateData);
        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member updated successfully.');
    }

    public function destroy($id)
    {
        $activeTerm = $this->getActiveTerm();
        $faculty = User::where('faculty_id', $id)
            ->when($activeTerm, function($query) use ($activeTerm) {
                return $query->where('semester', $activeTerm->semester);
            })
            ->firstOrFail();
        $faculty->delete();
        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member deleted successfully.');
    }
}

