<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefenseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'defense_type',
        'status',
        'student_message',
        'coordinator_notes',
        'requested_at',
        'responded_at'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function defenseSchedule()
    {
        return $this->hasOne(DefenseSchedule::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    public function getDefenseTypeLabelAttribute()
    {
        return match($this->defense_type) {
            'proposal' => 'Proposal Defense',
            '60_percent' => '60% Progress Defense',
            '100_percent' => '100% Final Defense',
            default => 'Unknown Defense'
        };
    }
}
