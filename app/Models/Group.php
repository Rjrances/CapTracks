<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Group extends Model
{
    protected $fillable = ['name', 'description', 'faculty_id', 'academic_term_id', 'offering_id'];
    public function adviser()
    {
        return $this->belongsTo(User::class, 'faculty_id', 'faculty_id');
    }
    public function members()
    {
        return $this->belongsToMany(Student::class, 'group_members', 'group_id', 'student_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }
    public function adviserInvitations()
    {
        return $this->hasMany(AdviserInvitation::class);
    }
    public function groupInvitations()
    {
        return $this->hasMany(GroupInvitation::class);
    }
    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }
    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }
    public function defenseSchedules()
    {
        return $this->hasMany(DefenseSchedule::class);
    }
    public function defenseRequests()
    {
        return $this->hasMany(DefenseRequest::class);
    }
    public function pendingAdviserInvitation()
    {
        return $this->hasOne(AdviserInvitation::class)->where('status', 'pending');
    }
    public function groupMilestones()
    {
        return $this->hasMany(GroupMilestone::class);
    }
    public function groupMilestoneTasks()
    {
        return $this->hasManyThrough(GroupMilestoneTask::class, GroupMilestone::class);
    }
    public function milestones()
    {
        return $this->hasMany(GroupMilestone::class);
    }
    public function getOverallProgressPercentageAttribute()
    {
        $milestones = $this->groupMilestones;
        if ($milestones->isEmpty()) {
            return 0;
        }
        $totalProgress = $milestones->sum('progress_percentage');
        return round($totalProgress / $milestones->count());
    }
} 
