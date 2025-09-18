<?php
namespace App\Imports;
use App\Models\User;
use App\Models\FacultyAccount;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Hash;
class FacultyImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $roleName = $row['role'] ?? 'teacher';
        $validRoles = ['teacher', 'adviser', 'panelist'];
        if (!in_array(strtolower($roleName), $validRoles)) {
            $roleName = 'teacher';
        }

        // Use faculty_id from CSV (required field)
        $facultyId = $row['faculty_id'];

        // Create the user first
        $user = new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'department' => $row['department'] ?? null,
            'role' => strtolower($roleName),
            'account_id' => $facultyId,
        ]);
        $user->save();

        // Create faculty account
        FacultyAccount::create([
            'faculty_id' => $facultyId,
            'user_id' => $user->id,
            'email' => $row['email'],
            'password' => Hash::make('password123'),
        ]);

        return $user;
    }
    public function rules(): array
    {
        return [
            '*.faculty_id' => 'required|string|max:20|unique:users,account_id|unique:faculty_accounts,faculty_id',
            '*.name' => 'required|string|max:255',
            '*.email' => 'required|email|unique:users,email|unique:faculty_accounts,email',
            '*.role' => 'nullable|string|in:teacher,adviser,panelist',
            '*.department' => 'nullable|string|max:255',
        ];
    }
    public function customValidationMessages(): array
    {
        return [
            '*.faculty_id.required' => 'Faculty ID is required',
            '*.faculty_id.unique' => 'Faculty ID :input already exists in the system',
            '*.email.unique' => 'Email address already exists in the system',
            '*.role.in' => 'Role must be one of: teacher, adviser, or panelist. Defaults to teacher if not specified.',
        ];
    }
} 
