<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Hash;

class FacultyImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'school_id' => $row['school_id'],
            'role' => $row['role'] ?? 'adviser',
            'course' => $row['course'] ?? null,
            'password' => Hash::make('password123'), // Default password
            'must_change_password' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.email' => 'required|email|unique:users,email',
            '*.school_id' => 'required|string|unique:users,school_id',
            '*.role' => 'nullable|in:adviser,panelist',
            '*.course' => 'nullable|string|max:255',
        ];
    }
} 