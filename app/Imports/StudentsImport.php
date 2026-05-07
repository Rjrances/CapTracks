<?php
namespace App\Imports;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Services\StudentEnrollmentService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class StudentsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnError, SkipsEmptyRows, WithMapping, WithEvents
{
    protected $offeringId;
    protected $importedStudentIds = [];
    protected $enrollmentService;
    protected $importedStudents = [];
    
    public function __construct($offeringId = null)
    {
        $this->offeringId = $offeringId;
        $this->enrollmentService = new StudentEnrollmentService();
    }
    public function map($row): array
    {
        $normalized = $this->normalizeStudentRow($row);
        $offerCode = trim((string) ($normalized['offer_code'] ?? ''));

        return [
            'student_id' => (string) $normalized['student_id'], // Force to string to handle Excel numeric conversion
            'name' => $this->composeStudentName(
                $normalized['first_name'] ?? '',
                $normalized['middle_name'] ?? '',
                $normalized['last_name'] ?? '',
                $normalized['suffix'] ?? ''
            ),
            'email' => trim((string) ($normalized['email'] ?? '')),
            'semester' => trim((string) ($normalized['semester'] ?? '')),
            'course' => trim((string) ($normalized['course'] ?? '')),
            'offer_code' => $offerCode === '' ? null : $offerCode,
        ];
    }
    public function model(array $row)
    {
        // Create the student first
        $student = new Student([
            'student_id' => $row['student_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'semester' => $row['semester'],
            'course' => $row['course'],
            'offer_code' => $row['offer_code'],
        ]);
        $student->save();

        // Create student account using the same ID as student_id
        StudentAccount::create([
            'student_id' => $student->student_id, // Use same ID as student_id
            'email' => $row['email'],
            'password' => null, // No password - must be set on first login
            'must_change_password' => true, // Force password change on first login
        ]);

        // Store the student for later enrollment processing
        $this->importedStudents[] = $student;

        if ($this->offeringId) {
            $this->importedStudentIds[] = $row['student_id'];
        }
        return $student;
    }
    public function rules(): array
    {
        return [
            '*.student_id' => [
                'required',
                'string',
                'unique:students,student_id',
                'regex:/^\d{10}$/', // Must be exactly 10 digits
            ],
            '*.name' => 'nullable|string|max:255',
            '*.first_name' => 'nullable|string|max:255',
            '*.middle_name' => 'nullable|string|max:255',
            '*.last_name' => 'nullable|string|max:255',
            '*.suffix' => 'nullable|string|max:20',
            '*.email' => 'nullable|email|unique:students,email|unique:student_accounts,email',
            '*.semester' => 'required|string|in:2024-2025 First Semester,2024-2025 Second Semester,2024-2025 Summer',
            '*.course' => 'required|string|max:255',
            '*.offer_code' => 'nullable|string|max:20|exists:offerings,offer_code',
        ];
    }
    public function prepareForValidation($data, $index)
    {
        if (isset($data['student_id'])) {
            $data['student_id'] = (string) $data['student_id'];
            if (strlen($data['student_id']) < 10) {
                $data['student_id'] = str_pad($data['student_id'], 10, '0', STR_PAD_LEFT);
            }
        }
        if (isset($data['offer_code']) && trim((string) $data['offer_code']) === '') {
            $data['offer_code'] = null;
        }

        return $data;
    }
    public function customValidationMessages(): array
    {
        return [
            '*.student_id.required' => 'Student ID is required on row :index.',
            '*.student_id.unique' => 'Student ID :input already exists in the system on row :index.',
            '*.student_id.regex' => 'Student ID must be exactly 10 digits on row :index.',
            '*.email.email' => 'Invalid email format on row :index.',
            '*.email.unique' => 'Email :input already exists in the system on row :index.',
            '*.semester.required' => 'Semester is required on row :index.',
            '*.course.required' => 'Course is required on row :index.',
            '*.offer_code.exists' => 'Offer code :input does not exist in the system on row :index.',
        ];
    }
    public function batchSize(): int
    {
        return 100;
    }
    public function chunkSize(): int
    {
        return 100;
    }
    public function onError(\Throwable $e)
    {
        Log::error('Student import error on row: ' . $e->getMessage());
    }
    public function afterImport()
    {
        try {
            // Process automatic enrollment based on offer_code
            if (!empty($this->importedStudents)) {
                $enrollmentResults = $this->enrollmentService->enrollStudentsByOfferCode(collect($this->importedStudents));
                $stats = $this->enrollmentService->getEnrollmentStats($enrollmentResults);
                
                Log::info("Student import enrollment results:", $stats);
                
                // Log successful enrollments
                if (!empty($enrollmentResults['enrolled'])) {
                    foreach ($enrollmentResults['enrolled'] as $result) {
                        Log::info("Student {$result['student']->student_id} enrolled in offering {$result['offering']->subject_code}");
                    }
                }
                
                // Log failed enrollments
                if (!empty($enrollmentResults['failed'])) {
                    foreach ($enrollmentResults['failed'] as $result) {
                        Log::warning("Failed to enroll student {$result['student']->student_id}: {$result['reason']}");
                    }
                }
                
                // Log offerings not found
                if (!empty($enrollmentResults['not_found'])) {
                    foreach ($enrollmentResults['not_found'] as $result) {
                        Log::warning("Offering not found for student {$result['student']->student_id} with offer code: {$result['offer_code']}");
                    }
                }
            }
            
            // Legacy support for offeringId-based enrollment
            if ($this->offeringId && !empty($this->importedStudentIds)) {
                $offering = \App\Models\Offering::find($this->offeringId);
                if ($offering) {
                    $studentsToEnroll = \App\Models\Student::whereIn('student_id', $this->importedStudentIds)->get();
                    if ($studentsToEnroll->count() > 0) {
                        foreach ($studentsToEnroll as $student) {
                            $student->enrollInOffering($offering);
                        }
                        Log::info("Legacy enrollment: Automatically enrolled {$studentsToEnroll->count()} students in offering {$offering->subject_code}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing student enrollments: ' . $e->getMessage());
        }
    }
    public function registerEvents(): array
    {
        return [
            AfterImport::class => function(AfterImport $event) {
                $this->afterImport();
            },
        ];
    }

    private function composeStudentName(string $firstName, string $middleName, string $lastName, string $suffix): string
    {
        $firstName = trim($firstName);
        $middleName = trim($middleName);
        $lastName = trim($lastName);
        $suffix = trim($suffix);

        if ($firstName === '' && $lastName === '') {
            return '';
        }

        $fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName, $suffix])));

        return $fullName;
    }

    private function normalizeStudentRow(array $row): array
    {
        // Legacy shape: student_id,name,email,semester,course,offer_code
        if (!empty($row['name']) && !empty($row['email'])) {
            [$firstName, $middleName, $lastName, $suffix] = $this->splitLegacyName((string) $row['name']);

            return [
                'student_id' => $row['student_id'] ?? '',
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'suffix' => $suffix,
                'email' => $row['email'] ?? '',
                'semester' => $row['semester'] ?? '',
                'course' => $row['course'] ?? '',
                'offer_code' => $row['offer_code'] ?? '',
            ];
        }

        $firstName = trim((string) ($row['first_name'] ?? ''));
        $middleName = trim((string) ($row['middle_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));
        $suffix = trim((string) ($row['suffix'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $semester = trim((string) ($row['semester'] ?? ''));
        $course = trim((string) ($row['course'] ?? ''));
        $offerCode = trim((string) ($row['offer_code'] ?? ''));

        // Misaligned shape after header update but old row values:
        // student_id,first_name(full name),email,semester,course,offer_code
        if (
            $email === '' &&
            filter_var($middleName, FILTER_VALIDATE_EMAIL) &&
            $semester === '' &&
            $course === ''
        ) {
            $email = $middleName;
            $semester = $lastName;
            $course = $suffix;
            $offerCode = $row['email'] ?? '';
            [$firstName, $middleName, $lastName, $suffix] = $this->splitLegacyName($firstName);
        }

        if (($firstName === '' || $lastName === '') && !empty($row['name'])) {
            [$parsedFirstName, $parsedMiddleName, $parsedLastName, $parsedSuffix] = $this->splitLegacyName((string) $row['name']);
            $firstName = $firstName !== '' ? $firstName : $parsedFirstName;
            $middleName = $middleName !== '' ? $middleName : $parsedMiddleName;
            $lastName = $lastName !== '' ? $lastName : $parsedLastName;
            $suffix = $suffix !== '' ? $suffix : $parsedSuffix;
        }

        return [
            'student_id' => $row['student_id'] ?? '',
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'suffix' => $suffix,
            'email' => $email,
            'semester' => $semester,
            'course' => $course,
            'offer_code' => $offerCode,
        ];
    }

    private function splitLegacyName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['', '', '', ''];
        }

        // "Last, First Middle Suffix" format.
        if (str_contains($name, ',')) {
            [$lastPart, $restPart] = array_map('trim', explode(',', $name, 2));
            $restTokens = preg_split('/\s+/', $restPart) ?: [];
            $suffix = '';
            if (!empty($restTokens) && preg_match('/^(Jr\\.?|Sr\\.?|I{2,3}|IV|V)$/i', (string) end($restTokens))) {
                $suffix = array_pop($restTokens);
            }
            $firstName = $restTokens[0] ?? '';
            $middleName = count($restTokens) > 1 ? implode(' ', array_slice($restTokens, 1)) : '';

            return [$firstName, $middleName, $lastPart, $suffix];
        }

        $tokens = preg_split('/\s+/', $name) ?: [];
        if (count($tokens) === 1) {
            return [$tokens[0], '', $tokens[0], ''];
        }

        $suffix = '';
        if (preg_match('/^(Jr\\.?|Sr\\.?|I{2,3}|IV|V)$/i', (string) end($tokens))) {
            $suffix = array_pop($tokens);
        }
        $firstName = array_shift($tokens) ?? '';
        $lastName = array_pop($tokens) ?? '';
        $middleName = !empty($tokens) ? implode(' ', $tokens) : '';

        return [$firstName, $middleName, $lastName, $suffix];
    }
}
