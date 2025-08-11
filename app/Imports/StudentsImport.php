<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithCustomValidationMessages;
use Illuminate\Support\Facades\Hash;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation, WithCustomValidationMessages
{
    public function model(array $row)
    {
        return new Student([
            'student_id' => $row['student_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'semester' => $row['semester'],
            'course' => $row['course'],
            'password' => Hash::make('password123'), // Default password
        ]);
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

    public function customValidationMessages(): array
    {
        return [
            '*.student_id.regex' => 'Student ID must be exactly 10 digits (e.g., 2024000001)',
            '*.student_id.unique' => 'Student ID already exists in the system',
            '*.email.unique' => 'Email address already exists in the system',
        ];
    }
}