<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // assuming 'role' is a string column
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * ================================
     *        RELATIONSHIPS
     * ================================
     */

    // If a teacher has many offerings
    public function offerings()
    {
        return $this->hasMany(Offering::class, 'teacher_id');
    }

    // If a student has many schedules
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'student_id');
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
        return $this->role === 'student';
    }
}
