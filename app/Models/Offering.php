<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offering extends Model
{
    use HasFactory;

    // Use default id as primary key
    // protected $primaryKey = 'id';
    // public $incrementing = true;
    // protected $keyType = 'int';

    protected $fillable = [
        'subject_title',
        'subject_code',
        'offer_code',
        'faculty_id',
        'academic_term_id',
    ];

    protected $casts = [
        'academic_term_id' => 'integer',
    ];

    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id', 'faculty_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'faculty_id', 'faculty_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'offering_student', 'offering_id', 'student_id', 'id', 'student_id')
                    ->withPivot('enrolled_at')
                    ->withTimestamps();
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    // Accessor for teacher name
    public function getTeacherNameAttribute()
    {
        return $this->teacher ? $this->teacher->name : 'N/A';
    }

    // Accessor for coordinator name (assuming the teacher is also the coordinator)
    public function getCoordinatorNameAttribute()
    {
        return $this->teacher ? $this->teacher->name : 'N/A';
    }
}
