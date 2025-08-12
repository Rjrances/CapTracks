<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'description', 'adviser_id', 'academic_term_id', 'offering_id'];

    // Adviser relationship
    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    // Group members relationship
    public function members()
    {
        return $this->belongsToMany(Student::class, 'group_members', 'group_id', 'student_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // Adviser invitations relationship
    public function adviserInvitations()
    {
        return $this->hasMany(AdviserInvitation::class);
    }

    // Academic term relationship
    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    // Offering relationship
    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    // Defense schedules relationship
    public function defenseSchedules()
    {
        return $this->hasMany(DefenseSchedule::class);
    }

    // Defense requests relationship
    public function defenseRequests()
    {
        return $this->hasMany(DefenseRequest::class);
    }

    // Active adviser invitation (pending)
    public function pendingAdviserInvitation()
    {
        return $this->hasOne(AdviserInvitation::class)->where('status', 'pending');
    }

    // ✅ NEW: Group milestones relationship
    public function groupMilestones()
    {
        return $this->hasMany(GroupMilestone::class);
    }

    // ✅ NEW: Group milestone tasks relationship
    public function groupMilestoneTasks()
    {
        return $this->hasManyThrough(GroupMilestoneTask::class, GroupMilestone::class);
    }

    // Alias for milestones relationship
    public function milestones()
    {
        return $this->hasMany(GroupMilestone::class);
    }

    // ✅ NEW: Calculate overall group progress percentage
    public function getOverallProgressPercentageAttribute()
    {
        $milestones = $this->groupMilestones;
        if ($milestones->isEmpty()) {
            return 0;
        }

        $totalProgress = $milestones->sum('progress_percentage');
        return round($totalProgress / $milestones->count());
    }

    // ✅ NEW: Check if group is ready for 60% defense
    public function isReadyFor60PercentDefense()
    {
        // Check if overall progress is at least 60%
        if ($this->overall_progress_percentage < 60) {
            return false;
        }

        // Check if adviser is assigned
        if (!$this->adviser_id) {
            return false;
        }

        // Check if required milestones are completed
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

    // ✅ NEW: Get readiness status for 60% defense
    public function get60PercentDefenseReadinessAttribute()
    {
        if ($this->isReadyFor60PercentDefense()) {
            return 'Ready';
        }

        $issues = [];
        
        if ($this->overall_progress_percentage < 60) {
            $issues[] = "Overall progress is {$this->overall_progress_percentage}% (needs 60%)";
        }
        
        if (!$this->adviser_id) {
            $issues[] = "No adviser assigned";
        }

        return implode(', ', $issues);
    }

    // ✅ NEW: Get required documents for 60% defense
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

    // ✅ NEW: Check if required documents are submitted
    public function hasRequiredDocumentsFor60PercentDefense()
    {
        $requiredDocs = $this->getRequiredDocumentsFor60PercentDefense();
        $submittedDocs = $this->members->flatMap->submissions->pluck('type')->unique();
        
        return count(array_intersect(array_keys($requiredDocs), $submittedDocs->toArray())) >= 4; // At least 4 out of 6
    }
} 