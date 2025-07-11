<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'school_id',      // ✅ new
        'name',
        'email',
        'birthday',       // ✅ new
        'course',         // ✅ new
        'year',           // ✅ new
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
