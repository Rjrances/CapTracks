<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Student([
            'student_id' => $row['student_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'semester' => $row['semester'],
            'course' => $row['course'],
        ]);
    }

    public function rules(): array
    {
        return [
            '*.student_id' => 'required|unique:students,student_id',
            '*.email' => 'nullable|email|unique:students,email',
            '*.semester' => 'required',
        ];
    }
}