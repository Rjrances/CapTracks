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
    public function isReadyFor60PercentDefense()
    {
        if ($this->overall_progress_percentage < 60) {
            return false;
        }
        if (!$this->faculty_id) {
            return false;
        }
        $requiredMilestones = $this->groupMilestones()
            ->whereIn('milestone_template_id', [1, 2, 3]) // Proposal, Literature Review, Methodology
            ->get();
        foreach ($requiredMilestones as $milestone) {
            if ($milestone->progress_percentage < 80) {
                return false;
            }
        }
        return true;
    }
    public function get60PercentDefenseReadinessAttribute()
    {
        if ($this->isReadyFor60PercentDefense()) {
            return 'Ready';
        }
        $issues = [];
        if ($this->overall_progress_percentage < 60) {
            $issues[] = "Overall progress is {$this->overall_progress_percentage}% (needs 60%)";
        }
        if (!$this->faculty_id) {
            $issues[] = "No adviser assigned";
        }
        return implode(', ', $issues);
    }
    public function getRequiredDocumentsFor60PercentDefense()
    {
        return [
            'progress_report' => 'Progress Report',
            'implementation_status' => 'Implementation Status Document',
            'methodology_validation' => 'Methodology Validation',
            'initial_results' => 'Initial Results and Findings',
            'problem_documentation' => 'Problem/Challenge Documentation',
            'next_phase_plan' => 'Next Phase Planning Document'
        ];
    }
    public function hasRequiredDocumentsFor60PercentDefense()
    {
        $requiredDocs = $this->getRequiredDocumentsFor60PercentDefense();
        $submittedDocs = $this->members->flatMap->submissions->pluck('type')->unique();
        return count(array_intersect(array_keys($requiredDocs), $submittedDocs->toArray())) >= 4; // At least 4 out of 6
    }
} 
