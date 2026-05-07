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
        [$firstName, $middleName, $lastName] = $this->extractNameParts($row);
        $namePrefix = isset($row['name_prefix']) ? trim((string) $row['name_prefix']) : null;
        $suffix = isset($row['suffix']) ? trim((string) $row['suffix']) : null;
        $displayName = trim(implode(' ', array_filter([
            $namePrefix,
            $firstName,
            $middleName,
            $lastName,
            $suffix,
        ])));

        // Create the user first
        $user = new User([
            'name' => $displayName,
            'name_prefix' => $namePrefix ?: null,
            'first_name' => $firstName,
            'middle_name' => $middleName ?: null,
            'last_name' => $lastName,
            'suffix' => $suffix ?: null,
            'email' => $row['email'],
            'department' => $row['department'] ?? null,
            'role' => strtolower($roleName),
            'faculty_id' => $facultyId,
            'semester' => $semester,
        ]);
        $user->save();
        $user->assignRoles([strtolower($roleName)]);

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
            '*.name' => 'nullable|string|max:255',
            '*.name_prefix' => 'nullable|string|max:20',
            '*.first_name' => 'required|string|max:100',
            '*.middle_name' => 'nullable|string|max:100',
            '*.last_name' => 'required|string|max:100',
            '*.suffix' => 'nullable|string|max:20',
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

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        if ($firstName === '' || $lastName === '') {
            [$parsedFirstName, $parsedMiddleName, $parsedLastName] = $this->extractNameParts($data);
            $data['first_name'] = $firstName !== '' ? $firstName : $parsedFirstName;
            $data['middle_name'] = trim((string) ($data['middle_name'] ?? '')) !== ''
                ? $data['middle_name']
                : $parsedMiddleName;
            $data['last_name'] = $lastName !== '' ? $lastName : $parsedLastName;
        }

        return $data;
    }

    public function customValidationMessages(): array
    {
        return [
            '*.faculty_id.required' => 'Faculty ID is required on row :index.',
            '*.faculty_id.unique' => 'Faculty ID :input already exists in the system on row :index.',
            '*.first_name.required' => 'First name is required on row :index.',
            '*.last_name.required' => 'Last name is required on row :index.',
            '*.email.unique' => 'Email address already exists in the system on row :index.',
            '*.role.in' => 'Role must be one of: teacher, adviser, panelist, coordinator, or chairperson. Defaults to teacher if not specified.',
        ];
    }

    private function extractNameParts(array $row): array
    {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $middleName = trim((string) ($row['middle_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));

        if ($firstName !== '' && $lastName !== '') {
            return [$firstName, $middleName, $lastName];
        }

        $fullName = trim((string) ($row['name'] ?? ''));
        $segments = preg_split('/\s+/', $fullName) ?: [];

        if (count($segments) === 1) {
            return [$segments[0], '', $segments[0]];
        }

        if (count($segments) >= 2) {
            $first = array_shift($segments);
            $last = array_pop($segments);
            $middle = implode(' ', $segments);

            return [$first, $middle, $last];
        }

        return ['', '', ''];
    }
} 
