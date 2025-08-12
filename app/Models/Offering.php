<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offering extends Model
{
    protected $fillable = ['subject_title', 'subject_code', 'teacher_id', 'academic_term_id'];

    // Academic term relationship
    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    // Teacher relationship
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Students relationship (many-to-many)
    public function students()
    {
        return $this->belongsToMany(Student::class, 'offering_student')
                    ->withTimestamps();
    }

    // Get teacher name attribute
    public function getTeacherNameAttribute()
    {
        return $this->teacher ? $this->teacher->name : 'No Teacher Assigned';
    }

    // Get coordinator name attribute (same as teacher for this system)
    public function getCoordinatorNameAttribute()
    {
        return $this->teacher ? $this->teacher->name : 'No Coordinator Assigned';
    }

    // Check if a user is the coordinator of this offering
    public function isCoordinatedBy($user)
    {
        return $user && $this->teacher_id === $user->id;
    }

    // Get enrolled students count
    public function getEnrolledStudentsCountAttribute()
    {
        return $this->students()->count();
    }
}
