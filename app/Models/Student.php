<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'student_id',
        'name',
        'email',
        'course',
        'year',
        'semester',
        'password',
        'must_change_password',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'student_id', 'group_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // ✅ NEW: Add submissions relationship
    public function submissions()
    {
        return $this->hasMany(ProjectSubmission::class);
    }

    // Offerings relationship (many-to-many)
    public function offerings()
    {
        return $this->belongsToMany(Offering::class, 'offering_student')
                    ->withTimestamps();
    }

    protected $casts = [
        'must_change_password' => 'boolean',
    ];
}
