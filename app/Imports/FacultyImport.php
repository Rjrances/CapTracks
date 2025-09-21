<?php
namespace App\Imports;
use App\Models\User;
use App\Models\UserAccount;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Hash;
class FacultyImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $semester;

    public function __construct($semester = null)
    {
        $this->semester = $semester;
    }

    public function model(array $row)
    {
        $roleName = $row['role'] ?? 'teacher';
        $validRoles = ['teacher', 'adviser', 'panelist', 'coordinator', 'chairperson'];
        if (!in_array(strtolower($roleName), $validRoles)) {
            $roleName = 'teacher';
        }

        // Use faculty_id from CSV (required field)
        $facultyId = $row['faculty_id'];
        
        // Use semester from CSV or constructor
        $semester = $this->semester ?? $row['semester'] ?? null;

        // Create the user first
        $user = new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'department' => $row['department'] ?? null,
            'role' => strtolower($roleName),
            'faculty_id' => $facultyId,
            'semester' => $semester,
        ]);
        $user->save();

        // Create faculty account
        UserAccount::create([
            'faculty_id' => $facultyId,
            'email' => $row['email'],
            'password' => Hash::make('password123'),
            'must_change_password' => true,
        ]);

        return $user;
    }
    
    
    public function rules(): array
    {
        return [
            '*.faculty_id' => 'required|string|max:20|unique:users,faculty_id|unique:user_accounts,faculty_id',
            '*.name' => 'required|string|max:255',
            '*.email' => 'required|email|unique:users,email|unique:user_accounts,email',
            '*.role' => 'nullable|string|in:teacher,adviser,panelist,coordinator,chairperson',
            '*.department' => 'nullable|string|max:255',
            '*.semester' => 'required|string|in:2024-2025 First Semester,2024-2025 Second Semester,2024-2025 Summer',
        ];
    }
    public function prepareForValidation($data, $index)
    {
        if (isset($data['faculty_id'])) {
            $data['faculty_id'] = (string) $data['faculty_id'];
        }
        return $data;
    }

    public function customValidationMessages(): array
    {
        return [
            '*.faculty_id.required' => 'Faculty ID is required on row :index.',
            '*.faculty_id.unique' => 'Faculty ID :input already exists in the system on row :index.',
            '*.email.unique' => 'Email address already exists in the system on row :index.',
            '*.role.in' => 'Role must be one of: teacher, adviser, panelist, coordinator, or chairperson. Defaults to teacher if not specified.',
        ];
    }
} 
