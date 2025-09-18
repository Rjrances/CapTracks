<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Account extends Authenticatable
{
    protected $fillable = [
        'faculty_id',
        'student_id',
        'email',
        'password',
        'user_type',
        'user_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    // Relationships
    public function student()
    {
        return $this->hasOne(Student::class, 'account_id', 'student_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'account_id', 'faculty_id');
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
        return $this->user_type === 'student';
    }

    public function isFaculty()
    {
        return $this->user_type === 'faculty';
    }

    public function getAssociatedUser()
    {
        if ($this->isStudent()) {
            return $this->student;
        } elseif ($this->isFaculty()) {
            return $this->user;
        }
        return null;
    }
}
