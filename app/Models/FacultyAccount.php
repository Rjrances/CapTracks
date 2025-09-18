<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class FacultyAccount extends Authenticatable
{
    protected $table = 'faculty_accounts';
    
    protected $fillable = [
        'faculty_id',
        'user_id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
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
    public function isFaculty()
    {
        return true; // This is always a faculty account
    }

    public function getAssociatedUser()
    {
        return $this->user;
    }
}