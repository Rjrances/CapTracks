<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class FacultyAccount extends Authenticatable
{
    protected $table = 'user_accounts';
    
    protected $fillable = [
        'faculty_id',
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
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'faculty_id', 'faculty_id');
    }

    public function getAuthIdentifierName()
    {
        return 'email';
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function isFaculty()
    {
        return true;
    }

    public function getAssociatedUser()
    {
        return $this->user;
    }
}