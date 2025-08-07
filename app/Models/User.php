<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'school_id',      // Faculty/Staff ID
        'name',
        'email',
        'birthday',
        'department',     // Department instead of course
        'position',       // Position instead of year
        'password',
        'role',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthday' => 'date',
        'must_change_password' => 'boolean',
    ];

    /**
     * ================================
     *        RELATIONSHIPS
     * ================================
     */
    public function offerings()
    {
        return $this->hasMany(Offering::class, 'teacher_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'student_id');
    }

    public function adviserInvitations()
    {
        return $this->hasMany(\App\Models\AdviserInvitation::class, 'faculty_id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'email', 'email');
    }

    /**
     * ================================
     *        ROLE CHECK HELPERS
     * ================================
     */
    public function isChairperson(): bool
    {
        return $this->role === 'chairperson';
    }

    public function isCoordinator(): bool
    {
        return $this->role === 'coordinator';
    }

    public function isTeacher(): bool
    {
        return in_array($this->role, ['adviser', 'panelist']);
    }

    public function isStudent(): bool
    {
        return false; // Students are in separate table
    }
}
