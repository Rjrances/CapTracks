<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMilestoneTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_milestone_id',
        'milestone_task_id',
        'assigned_to',
        'is_completed',
        'completed_at',
        'completed_by',
        'notes',
        'deadline'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'deadline' => 'datetime'
    ];

    // Relationships
    public function groupMilestone()
    {
        return $this->belongsTo(GroupMilestone::class);
    }

    public function milestoneTask()
    {
        return $this->belongsTo(MilestoneTask::class);
    }

    public function assignedStudent()
    {
        return $this->belongsTo(Student::class, 'assigned_to');
    }

    public function completedByStudent()
    {
        return $this->belongsTo(Student::class, 'completed_by');
    }

    // ✅ NEW: Mark task as completed
    public function markAsCompleted($completedBy = null)
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => $completedBy
        ]);

        // Recalculate milestone progress
        $this->groupMilestone->calculateProgressPercentage();
    }

    // ✅ NEW: Mark task as incomplete
    public function markAsIncomplete()
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null
        ]);

        // Recalculate milestone progress
        $this->groupMilestone->calculateProgressPercentage();
    }

    // ✅ NEW: Check if task is overdue
    public function getIsOverdueAttribute()
    {
        return $this->deadline && $this->deadline->isPast() && !$this->is_completed;
    }

    // ✅ NEW: Get days remaining
    public function getDaysRemainingAttribute()
    {
        if (!$this->deadline) {
            return null;
        }
        
        return now()->diffInDays($this->deadline, false);
    }

    // ✅ NEW: Get status text
    public function getStatusTextAttribute()
    {
        if ($this->is_completed) {
            return 'Completed';
        } elseif ($this->is_overdue) {
            return 'Overdue';
        } else {
            return 'Pending';
        }
    }
}
