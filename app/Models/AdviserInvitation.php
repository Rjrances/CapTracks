<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdviserInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'faculty_id',
        'status',
        'message',
        'response_message',
        'responded_at'
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isDeclined()
    {
        return $this->status === 'declined';
    }
} 