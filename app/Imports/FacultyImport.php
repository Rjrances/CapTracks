<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UserAccount;
use App\Support\ImportAcademicFieldsResolver;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class FacultyImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $semester;

    protected $createdCount = 0;

    protected $updatedCount = 0;

    /** Existing rows where attributes or role assignment differed from the database. */
    protected $existingFacultyChangedCount = 0;

    public function __construct($semester = null)
    {
        $this->semester = $semester;
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getExistingFacultyChangedCount(): int
    {
        return $this->existingFacultyChangedCount;
    }

    public function getExistingFacultyUnchangedCount(): int
    {
        return max(0, $this->updatedCount - $this->existingFacultyChangedCount);
    }

    public function model(array $row)
    {
        $roleName = $row['role'] ?? 'teacher';
        $validRoles = ['teacher', 'adviser', 'panelist', 'coordinator', 'chairperson'];
        if (!in_array(strtolower($roleName), $validRoles)) {
            $roleName = 'teacher';
        }

        $facultyId = (string) $row['faculty_id'];
        $canonicalSemester = trim((string) ($row['semester'] ?? ''));
        $schoolYear = isset($row['school_year']) ? $this->normalizeOptionalString($row['school_year']) : null;

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

        $attributes = [
            'name' => $displayName,
            'name_prefix' => $namePrefix ?: null,
            'first_name' => $firstName,
            'middle_name' => $middleName ?: null,
            'last_name' => $lastName,
            'suffix' => $suffix ?: null,
            'email' => $row['email'],
            'department' => $row['department'] ?? null,
            'role' => strtolower($roleName),
            'semester' => $canonicalSemester,
            'school_year' => $schoolYear,
        ];

        $user = $this->resolveUser($row, $facultyId, $canonicalSemester);

        if ($user) {
            $rolesBefore = $user->getRoleNames()->sort()->values()->toArray();

            $user->fill($attributes);
            $attributesDirty = $user->isDirty();
            if ($attributesDirty) {
                $user->save();
            }

            $roleNorm = strtolower($roleName);
            $user->assignRoles([$roleNorm]);

            $rolesAfter = $user->getRoleNames()->sort()->values()->toArray();
            $rolesChanged = $rolesBefore !== $rolesAfter;

            if ($attributesDirty || $rolesChanged) {
                $this->existingFacultyChangedCount++;
            }

            $this->ensureUserAccount($user);
            $this->updatedCount++;

            return null;
        }

        $user = new User(array_merge($attributes, [
            'faculty_id' => $facultyId,
        ]));
        $user->save();
        $user->assignRoles([strtolower($roleName)]);

        UserAccount::create([
            'faculty_id' => $facultyId,
            'email' => $row['email'],
            'password' => Hash::make('password123'),
            'must_change_password' => true,
        ]);

        $this->createdCount++;

        return $user;
    }

    private function resolveUser(array $row, string $facultyId, string $semester): ?User
    {
        $email = trim((string) ($row['email'] ?? ''));

        $byFaculty = User::query()
            ->where('faculty_id', $facultyId)
            ->when($semester !== '', fn ($q) => $q->where('semester', $semester))
            ->first();

        if ($byFaculty) {
            return $byFaculty;
        }

        if ($email === '') {
            return null;
        }

        return User::query()
            ->where('email', $email)
            ->when($semester !== '', fn ($q) => $q->where('semester', $semester))
            ->first()
            ?? User::where('email', $email)->first();
    }

    private function ensureUserAccount(User $user): void
    {
        $email = (string) $user->email;
        $account = UserAccount::firstOrCreate(
            ['faculty_id' => $user->faculty_id],
            [
                'email' => $email,
                'password' => Hash::make('password123'),
                'must_change_password' => true,
            ]
        );

        if (!$account->wasRecentlyCreated && $email !== '' && $account->email !== $email) {
            $account->email = $email;
            $account->save();
        }
    }

    private function normalizeOptionalString($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $s = trim((string) $raw);

        return $s === '' ? null : $s;
    }

    public function rules(): array
    {
        return [
            '*.faculty_id' => 'required|string|max:20',
            '*.name' => 'nullable|string|max:255',
            '*.name_prefix' => 'nullable|string|max:20',
            '*.first_name' => 'required|string|max:100',
            '*.middle_name' => 'nullable|string|max:100',
            '*.last_name' => 'required|string|max:100',
            '*.suffix' => 'nullable|string|max:20',
            '*.email' => 'required|email',
            '*.role' => 'nullable|string|in:teacher,adviser,panelist,coordinator,chairperson',
            '*.department' => 'nullable|string|max:255',
            '*.semester' => 'required|string',
            '*.semester_normalized' => 'required|exists:academic_terms,semester',
            '*.school_year' => 'nullable|regex:/^\d{4}-\d{4}$/',
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

        $semesterVal = trim((string) ($data['semester'] ?? ''));
        if ($semesterVal === '' && $this->semester !== null && $this->semester !== '') {
            $data['semester'] = $this->semester;
        }

        $ac = ImportAcademicFieldsResolver::resolve($data);
        if (trim((string) ($data['semester'] ?? '')) !== '' && $ac['semester'] === '') {
            throw ValidationException::withMessages([
                'school_year' => ["Row {$index}: Enter school_year (e.g. 2025-2026) when semester is 1st, 2nd, or summer."],
            ]);
        }

        $data['semester'] = $ac['semester'];
        $data['school_year'] = $ac['school_year'];
        $data['semester_normalized'] = $ac['semester'];

        return $data;
    }

    public function customValidationMessages(): array
    {
        return [
            '*.faculty_id.required' => 'Faculty ID is required on row :index.',
            '*.first_name.required' => 'First name is required on row :index.',
            '*.last_name.required' => 'Last name is required on row :index.',
            '*.role.in' => 'Role must be one of: teacher, adviser, panelist, coordinator, or chairperson. Defaults to teacher if not specified.',
            '*.semester_normalized.exists' => 'No matching academic term on row :index. Create the term under Academic Terms first (same school year and semester wording).',
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
