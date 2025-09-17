<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Offering extends Model
{
    protected $fillable = ['subject_title', 'subject_code', 'teacher_id', 'academic_term_id'];
    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    public function students()
    {
        return $this->belongsToMany(Student::class, 'offering_student')
                    ->withTimestamps();
    }
    public function getTeacherNameAttribute()
    {
        return $this->teacher ? $this->teacher->name : 'No Teacher Assigned';
    }
    public function getCoordinatorNameAttribute()
    {
        return $this->teacher ? $this->teacher->name : 'No Coordinator Assigned';
    }
    public function isCoordinatedBy($user)
    {
        return $user && $this->teacher_id === $user->id;
    }
    public function getEnrolledStudentsCountAttribute()
    {
        return $this->students()->count();
    }
}
