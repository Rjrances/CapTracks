<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Offering extends Model
{
    protected $fillable = ['offer_code', 'subject_title', 'subject_code', 'teacher_id', 'academic_term_id'];
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
        return $this->belongsToMany(Student::class, 'offering_student', 'offering_id', 'student_id')
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
    
    // Add missing relationships based on your diagram
    public function groups()
    {
        return $this->hasMany(Group::class);
    }
    
    public function defenseSchedules()
    {
        return $this->hasManyThrough(DefenseSchedule::class, Group::class);
    }
    
    public function milestones()
    {
        return $this->hasManyThrough(GroupMilestone::class, Group::class);
    }
    
    // Add validation rules for offer_code and subject_code
    public static function rules()
    {
        return [
            'offer_code' => 'required|string|unique:offerings,offer_code',
            'subject_code' => 'required|in:CT1,CT2,T1,T2',
            'subject_title' => 'required|string',
            'teacher_id' => 'required|exists:users,id',
            'academic_term_id' => 'required|exists:academic_terms,id'
        ];
    }
    
    // Add enrollment management methods
    public function enrollStudent($studentId)
    {
        $this->students()->syncWithoutDetaching([$studentId]);
    }
    
    public function unenrollStudent($studentId)
    {
        $this->students()->detach($studentId);
    }
    
    public function isStudentEnrolled($studentId)
    {
        return $this->students()->where('student_id', $studentId)->exists();
    }
}
