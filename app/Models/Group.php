<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'description', 'adviser_id'];

    // Adviser relationship
    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    // Group members relationship
    public function members()
    {
        return $this->belongsToMany(Student::class, 'group_members', 'group_id', 'student_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // Adviser invitations relationship
    public function adviserInvitations()
    {
        return $this->hasMany(AdviserInvitation::class);
    }

    // Active adviser invitation (pending)
    public function pendingAdviserInvitation()
    {
        return $this->hasOne(AdviserInvitation::class)->where('status', 'pending');
    }

    // Placeholder: milestones relationship (to be implemented if milestone table exists)
    // public function milestones()
    // {
    //     return $this->hasMany(Milestone::class);
    // }
} 