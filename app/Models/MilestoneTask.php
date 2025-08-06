<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilestoneTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'milestone_template_id', 
        'name', 
        'description', 
        'order',
        'is_completed', // ✅ NEW: Track completion status
        'completed_at', // ✅ NEW: Track when completed
        'assigned_to', // ✅ NEW: Track who it's assigned to
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // Each MilestoneTask belongs to a MilestoneTemplate
    public function milestoneTemplate()
    {
        return $this->belongsTo(MilestoneTemplate::class);
    }

    // ✅ NEW: Calculate progress percentage for this task
    public function getProgressPercentageAttribute()
    {
        return $this->is_completed ? 100 : 0;
    }

    // ✅ NEW: Mark task as completed
    public function markAsCompleted()
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    // ✅ NEW: Mark task as incomplete
    public function markAsIncomplete()
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);
    }
}
