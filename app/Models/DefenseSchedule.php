<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefenseSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'stage',
        'academic_term_id',
        'start_at',
        'end_at',
        'room',
        'remarks',
        'status'
    ];

    protected $attributes = [
        'status' => 'scheduled'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function panels()
    {
        return $this->hasMany(DefensePanel::class);
    }

    public function panelists()
    {
        return $this->belongsToMany(User::class, 'defense_panels', 'defense_schedule_id', 'faculty_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // Helper methods
    public function getDurationAttribute()
    {
        return $this->start_at->diffInMinutes($this->end_at);
    }

    public function getFormattedTimeAttribute()
    {
        return $this->start_at->format('M j, Y g:i A') . ' - ' . $this->end_at->format('g:i A');
    }

    public function getFormattedDateAttribute()
    {
        return $this->start_at->format('M j, Y');
    }

    public function getFormattedStartTimeAttribute()
    {
        return $this->start_at->format('g:i A');
    }

    public function getFormattedEndTimeAttribute()
    {
        return $this->end_at->format('g:i A');
    }

    public function isConflicting($startAt, $endAt, $room, $excludeId = null)
    {
        $query = static::where('room', $room)
            ->where('status', 'scheduled')
            ->where(function ($q) use ($startAt, $endAt) {
                $q->whereBetween('start_at', [$startAt, $endAt])
                  ->orWhereBetween('end_at', [$startAt, $endAt])
                  ->orWhere(function ($q2) use ($startAt, $endAt) {
                      $q2->where('start_at', '<=', $startAt)
                         ->where('end_at', '>=', $endAt);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
