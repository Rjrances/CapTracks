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
        'role',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function defenseSchedule()
    {
        return $this->belongsTo(DefenseSchedule::class, 'defense_schedule_id');
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    public function accept(): void
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    public function decline(): void
    {
        $this->update([
            'status' => 'declined',
            'responded_at' => now(),
        ]);
    }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'adviser'     => 'Adviser',
            'coordinator' => 'Coordinator',
            'chair'       => 'Chair',
            'member'      => 'Member',
            default       => 'Unknown Role',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'  => 'Pending',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            default    => 'Unknown',
        };
    }
}
