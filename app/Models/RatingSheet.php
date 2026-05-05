<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatingSheet extends Model
{
    protected $fillable = [
        'defense_schedule_id',
        'faculty_id',
        'group_id',
        'criteria',
        'individual_scores',
        'total_score',
        'recommendation',
        'recommendation_reason',
        'remarks',
        'submitted_at',
    ];

    protected $casts = [
        'criteria' => 'array',
        'individual_scores' => 'array',
        'submitted_at' => 'datetime',
        'total_score' => 'decimal:2',
    ];

    public function defenseSchedule()
    {
        return $this->belongsTo(DefenseSchedule::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
