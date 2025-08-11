<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'milestone_template_id',
        'progress_percentage',
        'start_date',
        'target_date',
        'completed_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'completed_date' => 'date',
        'progress_percentage' => 'integer'
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function milestoneTemplate()
    {
        return $this->belongsTo(MilestoneTemplate::class);
    }

    // Alias for template relationship
    public function template()
    {
        return $this->belongsTo(MilestoneTemplate::class, 'milestone_template_id');
    }

    public function groupTasks()
    {
        return $this->hasMany(GroupMilestoneTask::class);
    }

    // ✅ NEW: Calculate progress based on completed tasks
    public function calculateProgressPercentage()
    {
        $totalTasks = $this->groupTasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->groupTasks()->where('is_completed', true)->count();
        $percentage = round(($completedTasks / $totalTasks) * 100);
        
        $this->update(['progress_percentage' => $percentage]);
        
        return $percentage;
    }

    // ✅ NEW: Check if milestone is completed
    public function getIsCompletedAttribute()
    {
        return $this->progress_percentage >= 100;
    }

    // ✅ NEW: Get status text
    public function getStatusTextAttribute()
    {
        if ($this->progress_percentage >= 100) {
            return 'Completed';
        } elseif ($this->progress_percentage >= 80) {
            return 'Almost Done';
        } elseif ($this->progress_percentage >= 50) {
            return 'In Progress';
        } elseif ($this->progress_percentage > 0) {
            return 'Started';
        } else {
            return 'Not Started';
        }
    }

    // ✅ NEW: Check if overdue
    public function getIsOverdueAttribute()
    {
        return $this->target_date && $this->target_date->isPast() && $this->progress_percentage < 100;
    }

    // ✅ NEW: Get days remaining
    public function getDaysRemainingAttribute()
    {
        if (!$this->target_date) {
            return null;
        }
        
        return now()->diffInDays($this->target_date, false);
    }
}
