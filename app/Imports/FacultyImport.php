<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithCustomValidationMessages;
use Illuminate\Support\Facades\Hash;

class FacultyImport implements ToModel, WithHeadingRow, WithValidation, WithCustomValidationMessages
{
    public function model(array $row)
    {
        return new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'school_id' => $row['school_id'],
            'department' => $row['department'] ?? null,
            'position' => $row['position'] ?? null,
            'password' => Hash::make('password123'), // Default password
            'must_change_password' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.email' => 'required|email|unique:users,email',
            '*.school_id' => [
                'required',
                'string',
                'unique:users,school_id',
                'regex:/^\d{5}$/', // Must be exactly 5 digits
            ],
            '*.department' => 'nullable|string|max:255',
            '*.position' => 'nullable|string|max:255',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.school_id.regex' => 'Faculty/Staff ID must be exactly 5 digits (e.g., 12345)',
            '*.school_id.unique' => 'Faculty/Staff ID already exists in the system',
            '*.email.unique' => 'Email address already exists in the system',
        ];
    }
} 