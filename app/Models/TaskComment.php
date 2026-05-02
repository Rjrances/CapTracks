<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskComment extends Model
{
    protected $fillable = [
        'group_milestone_task_id',
        'user_id',
        'student_id',
        'body',
        'parent_id',
    ];

    public function groupMilestoneTask(): BelongsTo
    {
        return $this->belongsTo(GroupMilestoneTask::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentAuthor(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'parent_id')->latest();
    }
}
