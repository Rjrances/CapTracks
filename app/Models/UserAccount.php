<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UserAccount extends Authenticatable
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
        'must_change_password' => 'boolean',
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

    public function isUser()
    {
        return true;
    }

    public function getAssociatedUser()
    {
        return $this->user;
    }
}
