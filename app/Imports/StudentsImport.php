<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new User([
            'school_id' => $row['school_id'],
            'name' => $row['name'],
            'birthday' => $row['birthday'],
            'course' => $row['course'],
            'year' => $row['year'],
            'email' => $row['email'],
            'password' => null,
            'role' => 'student',
            'must_change_password' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.school_id' => 'required|unique:users,school_id',
            '*.email' => 'nullable|email|unique:users,email',
        ];
    }
}