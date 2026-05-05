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
        'title',
        'description',
        'progress_percentage',
        'start_date',
        'target_date',
        'due_date',
        'completed_date',
        'status',
        'notes'
    ];
    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
        'progress_percentage' => 'integer'
    ];
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    public function milestoneTemplate()
    {
        return $this->belongsTo(MilestoneTemplate::class);
    }
    public function template()
    {
        return $this->belongsTo(MilestoneTemplate::class, 'milestone_template_id');
    }
    public function groupTasks()
    {
        return $this->hasMany(GroupMilestoneTask::class);
    }
    public function calculateProgressPercentage()
    {
        $totalTasks = $this->groupTasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }
        $completedTasks = $this->groupTasks()->where('status', 'done')->count();
        $percentage = round(($completedTasks / $totalTasks) * 100);
        $this->update(['progress_percentage' => $percentage]);
        return $percentage;
    }
    public function getIsCompletedAttribute()
    {
        return $this->progress_percentage >= 100;
    }
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
    public function getIsOverdueAttribute()
    {
        return $this->target_date && $this->target_date->isPast() && $this->progress_percentage < 100;
    }
    public function getDaysRemainingAttribute()
    {
        if (!$this->target_date) {
            return null;
        }
        return now()->diffInDays($this->target_date, false);
    }

    public function totalTasksCount(): int
    {
        return $this->relationLoaded('groupTasks')
            ? $this->groupTasks->count()
            : $this->groupTasks()->count();
    }

    public function completedTasksCount(): int
    {
        if ($this->relationLoaded('groupTasks')) {
            return $this->groupTasks
                ->filter(fn (GroupMilestoneTask $t) => $t->status === 'done' || $t->is_completed)
                ->count();
        }

        return $this->groupTasks()
            ->where(function ($q) {
                $q->where('status', 'done')->orWhere('is_completed', true);
            })
            ->count();
    }

    /**
     * Label + badge class for coordinator read-only view (aligned with task + percent progress).
     *
     * @return array{label: string, class: string}
     */
    public function coordinatorDisplayStatus(): array
    {
        $pct = (int) $this->progress_percentage;
        $total = $this->totalTasksCount();
        $done = $this->completedTasksCount();

        if ($total > 0) {
            if ($done >= $total) {
                return ['label' => 'Completed', 'class' => 'success'];
            }
            if ($done > 0 || $pct > 0) {
                return ['label' => 'In progress', 'class' => 'info'];
            }

            return ['label' => 'Not started', 'class' => 'secondary'];
        }

        if ($pct >= 100) {
            return ['label' => 'Completed', 'class' => 'success'];
        }
        if ($pct > 0) {
            return ['label' => 'In progress', 'class' => 'info'];
        }

        return ['label' => 'Not started', 'class' => 'secondary'];
    }
}
