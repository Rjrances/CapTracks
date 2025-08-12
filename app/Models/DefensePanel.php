<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefensePanel extends Model
{
    use HasFactory;

    protected $fillable = [
        'defense_schedule_id',
        'faculty_id',
        'role'
    ];

    // Relationships
    public function defenseSchedule()
    {
        return $this->belongsTo(DefenseSchedule::class, 'defense_schedule_id');
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }







    public function getRoleLabelAttribute()
    {
        return match($this->role) {
            'adviser' => 'Adviser',
            'subject_coordinator' => 'Subject Coordinator',
            'panelist_1' => 'Panelist #1',
            'panelist_2' => 'Panelist #2',
            default => 'Unknown Role'
        };
    }
}
