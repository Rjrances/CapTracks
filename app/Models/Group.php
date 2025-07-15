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

    // Placeholder: members relationship (to be implemented if member table exists)
    // public function members()
    // {
    //     return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id');
    // }

    // Placeholder: milestones relationship (to be implemented if milestone table exists)
    // public function milestones()
    // {
    //     return $this->hasMany(Milestone::class);
    // }
} 