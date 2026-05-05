<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefenseEvaluationSummary extends Model
{
    protected $fillable = [
        'defense_schedule_id',
        'group_id',
        'finalized_by',
        'reopened_by',
        'final_average_score',
        'final_recommendation',
        'final_notes',
        'reopen_reason',
        'finalized_at',
        'reopened_at',
    ];

    protected $casts = [
        'final_average_score' => 'decimal:2',
        'finalized_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(DefenseSchedule::class, 'defense_schedule_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function finalizedBy()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function reopenedBy()
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
}

