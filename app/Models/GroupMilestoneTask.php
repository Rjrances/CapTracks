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
    public function markAsCompleted($completedBy = null)
    {
        $this->update([
            'is_completed' => true,
            'status' => 'done',
            'completed_at' => now(),
            'completed_by' => $completedBy
        ]);
        $this->groupMilestone->calculateProgressPercentage();
    }
    public function markAsIncomplete()
    {
        $this->update([
            'is_completed' => false,
            'status' => 'pending',
            'completed_at' => null,
            'completed_by' => null
        ]);
        $this->groupMilestone->calculateProgressPercentage();
    }
    public function updateStatus($status)
    {
        $this->update([
            'status' => $status,
            'is_completed' => $status === 'done'
        ]);
        $this->groupMilestone->calculateProgressPercentage();
    }
    public function getIsOverdueAttribute()
    {
        return $this->deadline && $this->deadline->isPast() && !$this->is_completed;
    }
    public function getDaysRemainingAttribute()
    {
        if (!$this->deadline) {
            return null;
        }
        return now()->diffInDays($this->deadline, false);
    }
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
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'done' => 'success',
            'doing' => 'warning',
            'pending' => 'secondary',
            default => 'secondary'
        };
    }
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    public function scopeDoing($query)
    {
        return $query->where('status', 'doing');
    }
    public function scopeDone($query)
    {
        return $query->where('status', 'done');
    }
}
