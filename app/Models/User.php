<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'school_id',
        'role',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Auto-generate school ID on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $year = now()->year;
            $lastId = self::whereYear('created_at', $year)->count() + 1;
            $user->school_id = 'CAP' . $year . '-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Relationship: A user (teacher) can have many offerings.
     */
    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class, 'teacher_id');
    }
}
