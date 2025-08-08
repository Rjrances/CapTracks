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
        return $this->belongsTo(DefenseSchedule::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }
}
