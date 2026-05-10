<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AcademicTerm;
use App\Services\FacultyImportService;

class ChairpersonFacultyController extends Controller
{
  
    private function getActiveTerm()
    {
        return AcademicTerm::where('is_active', true)->first();
    }

    
    public function index(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        $sortBy = $request->get('sort', 'faculty_id');
        $sortDirection = $request->get('direction', 'asc');
        
        $query = User::query()
            ->with('roles')
            ->withAnyRole(['teacher', 'adviser', 'panelist', 'coordinator', 'chairperson']);
        
        if ($activeTerm) {
            $query->where('academic_term_id', $activeTerm->id);
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
            'name'     => 'nullable|string|max:255',
            'name_prefix' => 'nullable|string|max:20',
            'first_name' => 'required_without:name|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required_without:name|string|max:100',
            'suffix' => 'nullable|string|max:20',
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
        
        [$firstName, $middleName, $lastName] = $this->resolveNameParts($request);
        $namePrefix = $request->filled('name_prefix') ? trim((string) $request->name_prefix) : null;
        $suffix = $request->filled('suffix') ? trim((string) $request->suffix) : null;
        $fullName = $this->buildFullName($namePrefix, $firstName, $middleName, $lastName, $suffix);
        
        if ($this->facultyNameAlreadyExists($firstName, $middleName, $lastName, $suffix, $activeTerm)) {
            return back()
                ->withInput()
                ->withErrors(['first_name' => 'A faculty member with the same first, middle, and last name already exists for this semester. Use a different suffix if this is a different person.']);
        }
        
        
        if ($activeTerm) {
            $existingEmail = User::where('email', $request->email)
                ->where('academic_term_id', $activeTerm->id)
                ->first();
            
            if ($existingEmail) {
                return back()->withErrors(['email' => 'The email has already been taken by another user in this semester.']);
            }
            
            $existingFacultyId = User::where('faculty_id', $request->faculty_id)
                ->where('academic_term_id', $activeTerm->id)
                ->first();
            
            if ($existingFacultyId) {
                return back()->withErrors(['faculty_id' => 'The faculty ID has already been taken by another user in this semester.']);
            }
        }
        
        $facultyId = $request->faculty_id;
        
        $user = User::create([
            'name'                 => $fullName,
            'name_prefix'          => $namePrefix,
            'first_name'           => $firstName,
            'middle_name'          => $middleName,
            'last_name'            => $lastName,
            'suffix'               => $suffix,
            'email'                => $request->email,
            'birthday'             => now()->subYears(30),
            'department'           => 'N/A',
            'role'                 => $request->role,
            'faculty_id'           => $facultyId,
            'academic_term_id'     => $activeTerm?->id,
        ]);
        $user->assignRoles([$request->role]);

        
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
            'name_prefix' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'suffix' => 'nullable|string|max:20',
            'email' => 'required|email',
            'faculty_id' => 'required|string|max:20',
            'department' => 'nullable|string|max:255',
        ], [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'faculty_id.required' => 'Faculty ID is required.',
            'faculty_id.string' => 'Faculty ID must be a string.',
            'faculty_id.max' => 'Faculty ID must not exceed 20 characters.',
        ]);

        [$firstName, $middleName, $lastName] = $this->resolveNameParts($request);
        $namePrefix = $request->filled('name_prefix') ? trim((string) $request->name_prefix) : null;
        $suffix = $request->filled('suffix') ? trim((string) $request->suffix) : null;
        $fullName = $this->buildFullName($namePrefix, $firstName, $middleName, $lastName, $suffix);

        if ($this->facultyNameAlreadyExists($firstName, $middleName, $lastName, $suffix, $activeTerm)) {
            return back()
                ->withInput()
                ->withErrors(['first_name' => 'A faculty member with the same first, middle, and last name already exists for this semester. Use a different suffix if this is a different person.']);
        }
        
        
        if ($activeTerm) {
            $existingEmail = User::where('email', $request->email)
                ->where('academic_term_id', $activeTerm->id)
                ->first();
            
            if ($existingEmail) {
                return back()->withErrors(['email' => 'The email has already been taken by another user in this semester.']);
            }
            
            $existingFacultyId = User::where('faculty_id', $request->faculty_id)
                ->where('academic_term_id', $activeTerm->id)
                ->first();
            
            if ($existingFacultyId) {
                return back()->withErrors(['faculty_id' => 'The faculty ID has already been taken by another user in this semester.']);
            }
        }
        
        $facultyId = $request->faculty_id;
        
        
        $user = User::create([
            'name' => $fullName,
            'name_prefix' => $namePrefix,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'suffix' => $suffix,
            'email' => $request->email,
            'department' => $request->department,
            'role' => 'teacher',
            'faculty_id' => $facultyId,
            'academic_term_id' => $activeTerm?->id,
        ]);
        $user->assignRoles(['teacher']);

        
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
        return app(FacultyImportService::class)->importFromRequest($request);
    }

    public function edit($id)
    {
        $activeTerm = $this->getActiveTerm();
        $query = User::where('faculty_id', $id);
        
        if ($activeTerm) {
            $query->where('academic_term_id', $activeTerm->id);
        }
        
        $teacher = $query->firstOrFail();
        return view('chairperson.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, $id)
    {
        $activeTerm = $this->getActiveTerm();
        $query = User::where('faculty_id', $id);
        
        if ($activeTerm) {
            $query->where('academic_term_id', $activeTerm->id);
        }
        
        $faculty = $query->firstOrFail();
        
        
        $request->validate([
            'name_prefix' => 'nullable|string|max:20',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'suffix' => 'nullable|string|max:20',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) use ($faculty) {
                    $existingUser = User::where('email', $value)
                        ->where('id', '!=', $faculty->id)
                        ->where('academic_term_id', $faculty->academic_term_id)
                        ->first();
                    
                    if ($existingUser) {
                        $fail('The email has already been taken by another user in this semester.');
                    }
                }
            ],
            'department' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
        ], [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
        ]);
        [$firstName, $middleName, $lastName] = $this->resolveNameParts($request);
        $namePrefix = $request->filled('name_prefix') ? trim((string) $request->name_prefix) : null;
        $suffix = $request->filled('suffix') ? trim((string) $request->suffix) : null;

        if ($this->facultyNameAlreadyExists($firstName, $middleName, $lastName, $suffix, $activeTerm, $faculty->id)) {
            return back()
                ->withInput()
                ->withErrors(['first_name' => 'A faculty member with the same first, middle, and last name already exists for this semester. Use a different suffix if this is a different person.']);
        }

        $fullName = $this->buildFullName($namePrefix, $firstName, $middleName, $lastName, $suffix);
        $updateData = [
            'name' => $fullName,
            'name_prefix' => $namePrefix,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'suffix' => $suffix,
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

    public function assignCoordinator($id)
    {
        $activeTerm = $this->getActiveTerm();
        $faculty = User::where('faculty_id', $id)
            ->when($activeTerm, function($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->firstOrFail();

        if ($faculty->hasRole('coordinator')) {
            return redirect()->route('chairperson.teachers.index')
                ->with('success', "{$faculty->name} is already assigned as coordinator.");
        }

        $faculty->assignRole('coordinator');

        return redirect()->route('chairperson.teachers.index')
            ->with('success', "{$faculty->name} has been assigned as coordinator.");
    }

    public function removeCoordinator($id)
    {
        $activeTerm = $this->getActiveTerm();
        $faculty = User::where('faculty_id', $id)
            ->when($activeTerm, function($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->firstOrFail();

        if (!$faculty->hasRole('coordinator')) {
            return redirect()->route('chairperson.teachers.edit', $faculty->faculty_id)
                ->with('success', "{$faculty->name} does not have coordinator access.");
        }

        $faculty->removeRole('coordinator');

        return redirect()->route('chairperson.teachers.edit', $faculty->faculty_id)
            ->with('success', "Coordinator access removed for {$faculty->name}.");
    }

    public function destroy($id)
    {
        $activeTerm = $this->getActiveTerm();
        $faculty = User::where('faculty_id', $id)
            ->when($activeTerm, function($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->firstOrFail();
        $faculty->delete();
        return redirect()->route('chairperson.teachers.index')->with('success', 'Faculty member deleted successfully.');
    }

    private function resolveNameParts(Request $request): array
    {
        $firstName = trim((string) $request->input('first_name', ''));
        $middleName = trim((string) $request->input('middle_name', ''));
        $lastName = trim((string) $request->input('last_name', ''));

        if ($firstName !== '' && $lastName !== '') {
            return [$firstName, $middleName ?: null, $lastName];
        }

        $fullName = trim((string) $request->input('name', ''));
        $segments = preg_split('/\s+/', $fullName) ?: [];

        if (count($segments) === 1) {
            return [$segments[0], null, $segments[0]];
        }

        if (count($segments) >= 2) {
            $first = array_shift($segments);
            $last = array_pop($segments);
            $middle = trim(implode(' ', $segments));

            return [$first, $middle !== '' ? $middle : null, $last];
        }

        return ['', null, ''];
    }

    private function buildFullName(?string $prefix, string $firstName, ?string $middleName, string $lastName, ?string $suffix): string
    {
        return trim(implode(' ', array_filter([
            $prefix,
            $firstName,
            $middleName,
            $lastName,
            $suffix,
        ], fn ($value) => filled($value))));
    }

    private function facultyNameAlreadyExists(
        string $firstName,
        ?string $middleName,
        string $lastName,
        ?string $suffix,
        ?AcademicTerm $activeTerm,
        ?int $excludeUserId = null
    ): bool
    {
        $query = User::query()
            ->whereRaw('LOWER(TRIM(COALESCE(first_name, ""))) = ?', [strtolower(trim($firstName))])
            ->whereRaw('LOWER(TRIM(COALESCE(middle_name, ""))) = ?', [strtolower(trim((string) $middleName))])
            ->whereRaw('LOWER(TRIM(COALESCE(last_name, ""))) = ?', [strtolower(trim($lastName))])
            ->whereRaw('LOWER(TRIM(COALESCE(suffix, ""))) = ?', [strtolower(trim((string) $suffix))]);

        if ($activeTerm) {
            $query->where('academic_term_id', $activeTerm->id);
        }

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->exists();
    }
}

