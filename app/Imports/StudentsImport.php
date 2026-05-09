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
use Illuminate\Support\Facades\Log;
class StudentsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnError, SkipsEmptyRows, WithMapping, WithEvents
{
    protected $offeringId;
    protected $importedStudentIds = [];
    protected $enrollmentService;
    protected $importedStudents = [];
    protected $createdStudentsCount = 0;
    protected $existingStudentsCount = 0;
    protected $existingStudentIds = [];
    
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
            'student_id' => (string) $normalized['student_id'], 
            'name_prefix' => trim((string) ($normalized['name_prefix'] ?? '')),
            'first_name' => trim((string) ($normalized['first_name'] ?? '')),
            'middle_name' => trim((string) ($normalized['middle_name'] ?? '')),
            'last_name' => trim((string) ($normalized['last_name'] ?? '')),
            'suffix' => trim((string) ($normalized['suffix'] ?? '')),
            'name' => $this->composeStudentName(
                $normalized['name_prefix'] ?? '',
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
        $student = Student::where('student_id', $row['student_id'])->first();
        if (!$student && !empty($row['email'])) {
            $student = Student::where('email', $row['email'])->first();
        }

        if ($student) {
            $this->existingStudentsCount++;
            $this->existingStudentIds[] = (string) $student->student_id;
            return null;
        }

        $student = new Student([
            'student_id' => $row['student_id'],
            'name' => $row['name'],
            'name_prefix' => $row['name_prefix'] ?: null,
            'first_name' => $row['first_name'] ?: null,
            'middle_name' => $row['middle_name'] ?: null,
            'last_name' => $row['last_name'] ?: null,
            'suffix' => $row['suffix'] ?: null,
            'email' => $row['email'],
            'semester' => $row['semester'],
            'course' => $row['course'],
            'offer_code' => $row['offer_code'],
        ]);
        $student->save();
        $this->createdStudentsCount++;

        
        StudentAccount::firstOrCreate(
            ['student_id' => $student->student_id],
            [
                'email' => $student->email ?: $row['email'],
                'password' => null, 
                'must_change_password' => true, 
            ]
        );

        
        $this->importedStudents[] = $student;

        if ($this->offeringId) {
            $this->importedStudentIds[] = $row['student_id'];
        }
        return $student;
    }

    public function getCreatedStudentsCount(): int
    {
        return $this->createdStudentsCount;
    }

    public function getExistingStudentsCount(): int
    {
        return $this->existingStudentsCount;
    }

    public function getExistingStudentIds(int $limit = 10): array
    {
        return array_slice(array_values(array_unique($this->existingStudentIds)), 0, $limit);
    }
    public function rules(): array
    {
        return [
            '*.student_id' => [
                'required',
                'string',
                'regex:/^\d{10}$/', 
            ],
            '*.name' => 'nullable|string|max:255',
            '*.name_prefix' => 'nullable|string|max:20',
            '*.first_name' => 'nullable|string|max:255',
            '*.middle_name' => 'nullable|string|max:255',
            '*.last_name' => 'nullable|string|max:255',
            '*.suffix' => 'nullable|string|max:20',
            '*.email' => 'nullable|email',
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
            '*.student_id.regex' => 'Student ID must be exactly 10 digits on row :index.',
            '*.email.email' => 'Invalid email format on row :index.',
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
            
            if (!empty($this->importedStudents)) {
                $enrollmentResults = $this->enrollmentService->enrollStudentsByOfferCode(collect($this->importedStudents));
                $stats = $this->enrollmentService->getEnrollmentStats($enrollmentResults);
                
                Log::info("Student import enrollment results:", $stats);
                
                
                if (!empty($enrollmentResults['enrolled'])) {
                    foreach ($enrollmentResults['enrolled'] as $result) {
                        Log::info("Student {$result['student']->student_id} enrolled in offering {$result['offering']->subject_code}");
                    }
                }
                
                
                if (!empty($enrollmentResults['failed'])) {
                    foreach ($enrollmentResults['failed'] as $result) {
                        Log::warning("Failed to enroll student {$result['student']->student_id}: {$result['reason']}");
                    }
                }
                
                
                if (!empty($enrollmentResults['not_found'])) {
                    foreach ($enrollmentResults['not_found'] as $result) {
                        Log::warning("Offering not found for student {$result['student']->student_id} with offer code: {$result['offer_code']}");
                    }
                }
            }
            
            
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

    private function composeStudentName(string $namePrefix, string $firstName, string $middleName, string $lastName, string $suffix): string
    {
        $namePrefix = trim($namePrefix);
        $firstName = trim($firstName);
        $middleName = trim($middleName);
        $lastName = trim($lastName);
        $suffix = trim($suffix);

        if ($firstName === '' && $lastName === '') {
            return '';
        }

        $fullName = trim(implode(' ', array_filter([$namePrefix, $firstName, $middleName, $lastName, $suffix])));

        return $fullName;
    }

    private function normalizeStudentRow(array $row): array
    {
        
        if (!empty($row['name']) && !empty($row['email'])) {
            [$firstName, $middleName, $lastName, $suffix] = $this->splitLegacyName((string) $row['name']);

            return [
                'student_id' => $row['student_id'] ?? '',
                'name_prefix' => '',
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
            'name_prefix' => trim((string) ($row['name_prefix'] ?? '')),
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
