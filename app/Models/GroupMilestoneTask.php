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
        'status',
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

    public function submissions()
    {
        return $this->hasMany(TaskSubmission::class);
    }

    // ✅ NEW: Mark task as completed
    public function markAsCompleted($completedBy = null)
    {
        $this->update([
            'is_completed' => true,
            'status' => 'done',
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
            'status' => 'pending',
            'completed_at' => null,
            'completed_by' => null
        ]);

        // Recalculate milestone progress
        $this->groupMilestone->calculateProgressPercentage();
    }

    // ✅ NEW: Update status
    public function updateStatus($status)
    {
        $this->update([
            'status' => $status,
            'is_completed' => $status === 'done'
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
        if ($this->status === 'done') {
            return 'Completed';
        } elseif ($this->status === 'doing') {
            return 'In Progress';
        } elseif ($this->is_overdue) {
            return 'Overdue';
        } else {
            return 'Pending';
        }
    }

    // ✅ NEW: Get status badge class
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'done' => 'success',
            'doing' => 'warning',
            'pending' => 'secondary',
            default => 'secondary'
        };
    }

    // ✅ NEW: Scope for pending tasks
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ✅ NEW: Scope for doing tasks
    public function scopeDoing($query)
    {
        return $query->where('status', 'doing');
    }

    // ✅ NEW: Scope for done tasks
    public function scopeDone($query)
    {
        return $query->where('status', 'done');
    }
}
