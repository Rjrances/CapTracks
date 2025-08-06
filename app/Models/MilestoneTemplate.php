<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilestoneTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'status'];

    // One MilestoneTemplate has many MilestoneTasks
    public function tasks()
    {
        return $this->hasMany(MilestoneTask::class)->orderBy('order');
    }

    // ✅ NEW: Calculate overall progress percentage for this milestone
    public function getProgressPercentageAttribute()
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()->where('is_completed', true)->count();
        return round(($completedTasks / $totalTasks) * 100);
    }

    // ✅ NEW: Get completed tasks count
    public function getCompletedTasksCountAttribute()
    {
        return $this->tasks()->where('is_completed', true)->count();
    }

    // ✅ NEW: Get total tasks count
    public function getTotalTasksCountAttribute()
    {
        return $this->tasks()->count();
    }

    // ✅ NEW: Check if milestone is completed
    public function getIsCompletedAttribute()
    {
        return $this->progress_percentage === 100;
    }

    // ✅ NEW: Get progress status text
    public function getProgressStatusAttribute()
    {
        $percentage = $this->progress_percentage;
        
        if ($percentage === 0) {
            return 'Not Started';
        } elseif ($percentage < 50) {
            return 'In Progress';
        } elseif ($percentage < 100) {
            return 'Almost Done';
        } else {
            return 'Completed';
        }
    }
}
