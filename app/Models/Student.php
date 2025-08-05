<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'email',
        'semester',
        'course',
    ];

    // Relationships
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'student_id', 'group_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
