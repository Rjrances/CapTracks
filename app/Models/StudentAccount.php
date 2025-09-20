<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class StudentAccount extends Authenticatable
{
    protected $table = 'student_accounts';
    
    protected $fillable = [
        'student_id',
        'email',
        'password',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'must_change_password' => 'boolean',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // Authentication methods
    public function getAuthIdentifierName()
    {
        return 'email';
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    // Helper methods
    public function isStudent()
    {
        return true; // This is always a student account
    }

    public function getAssociatedUser()
    {
        return $this->student;
    }
}