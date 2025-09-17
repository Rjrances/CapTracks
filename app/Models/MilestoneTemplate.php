<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MilestoneTemplate extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'status'];
    public function tasks()
    {
        return $this->hasMany(MilestoneTask::class)->orderBy('order');
    }
    public function getProgressPercentageAttribute()
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }
        $completedTasks = $this->tasks()->where('is_completed', true)->count();
        return round(($completedTasks / $totalTasks) * 100);
    }
    public function getCompletedTasksCountAttribute()
    {
        return $this->tasks()->where('is_completed', true)->count();
    }
    public function getTotalTasksCountAttribute()
    {
        return $this->tasks()->count();
    }
    public function getIsCompletedAttribute()
    {
        return $this->progress_percentage === 100;
    }
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
