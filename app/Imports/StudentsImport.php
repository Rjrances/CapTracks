<?php
namespace App\Imports;
use App\Models\Student;
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
    public function __construct($offeringId = null)
    {
        $this->offeringId = $offeringId;
    }
    public function map($row): array
    {
        return [
            'student_id' => (string) $row['student_id'], // Force to string to handle Excel numeric conversion
            'name' => trim($row['name'] ?? ''),
            'email' => trim($row['email'] ?? ''),
            'semester' => trim($row['semester'] ?? ''),
            'course' => trim($row['course'] ?? ''),
        ];
    }
    public function model(array $row)
    {
        $student = new Student([
            'student_id' => $row['student_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'semester' => $row['semester'],
            'course' => $row['course'],
            'password' => Hash::make('password123'), // Default password
        ]);
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
            '*.name' => 'required|string|max:255',
            '*.email' => 'nullable|email|unique:students,email',
            '*.semester' => 'required|string|max:50',
            '*.course' => 'required|string|max:255',
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
        return $data;
    }
    public function customValidationMessages(): array
    {
        return [
            '*.student_id.required' => 'Student ID is required on row :index.',
            '*.student_id.unique' => 'Student ID :input already exists in the system on row :index.',
            '*.student_id.regex' => 'Student ID must be exactly 10 digits on row :index.',
            '*.name.required' => 'Student name is required on row :index.',
            '*.email.email' => 'Invalid email format on row :index.',
            '*.email.unique' => 'Email :input already exists in the system on row :index.',
            '*.semester.required' => 'Semester is required on row :index.',
            '*.course.required' => 'Course is required on row :index.',
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
        if ($this->offeringId && !empty($this->importedStudentIds)) {
            try {
                $offering = \App\Models\Offering::find($this->offeringId);
                if ($offering) {
                    $studentsToEnroll = \App\Models\Student::whereIn('student_id', $this->importedStudentIds)->get();
                    if ($studentsToEnroll->count() > 0) {
                        foreach ($studentsToEnroll as $student) {
                            $student->enrollInOffering($offering);
                        }
                        Log::info("Automatically enrolled {$studentsToEnroll->count()} students in offering {$offering->subject_code}");
                        Log::info("Enrolled student IDs: " . implode(', ', $this->importedStudentIds));
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error auto-enrolling students: ' . $e->getMessage());
                Log::error('Student IDs that failed to enroll: ' . implode(', ', $this->importedStudentIds));
            }
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
}
