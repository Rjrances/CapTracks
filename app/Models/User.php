<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable
{
    use Notifiable, HasRoles;
    protected $fillable = [
        'faculty_id',
        'name',
        'name_prefix',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'birthday',
        'department',
        'role',
        'semester',
    ];

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $hidden = [
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birthday' => 'date',
    ];
    protected $guard_name = 'web';

    public function getAuthPassword()
    {
        return $this->account ? $this->account->password : null;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }
    public function account()
    {
        return $this->hasOne(UserAccount::class, 'faculty_id', 'faculty_id');
    }
    
    public function userRoles()
    {
        return $this->hasMany(\App\Models\UserRole::class, 'user_id');
    }
    public function offerings()
    {
        return $this->hasMany(Offering::class, 'faculty_id', 'faculty_id');
    }
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'student_id');
    }
    public function adviserInvitations()
    {
        return $this->hasMany(\App\Models\AdviserInvitation::class, 'faculty_id', 'id');
    }
    public function student()
    {
        return $this->hasOne(Student::class, 'email', 'email');
    }
    public function defensePanels()
    {
        return $this->hasMany(DefensePanel::class, 'faculty_id');
    }
    public function defenseSchedules()
    {
        return $this->belongsToMany(DefenseSchedule::class, 'defense_panels', 'faculty_id', 'defense_schedule_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }
    public function isChairperson(): bool
    {
        return $this->hasRole('chairperson');
    }
    public function isCoordinator(): bool
    {
        return $this->hasRole('coordinator');
    }
    public function isTeacher(): bool
    {
        return $this->hasAnyRole(['teacher', 'adviser', 'panelist', 'coordinator']);
    }
    public function isOfferingCoordinator(): bool
    {
        return $this->hasRole('coordinator') && $this->offerings()->exists();
    }
    public function getCoordinatedOfferings()
    {
        return $this->offerings()->with('academicTerm')->get();
    }
    public function canBeAdviserForGroup($groupId): bool
    {
        $group = \App\Models\Group::find($groupId);
        if (!$group) return false;
        $coordinatedOfferingIds = $this->offerings()->pluck('id')->toArray();
        return !in_array($group->offering_id, $coordinatedOfferingIds);
    }
    public function isStudent(): bool
    {
        return false;
    }
    public function getPrimaryRoleAttribute()
    {
        $roleNames = $this->getRoleNames()->toArray();

        if (in_array('coordinator', $roleNames)) {
            return 'coordinator';
        }

        return $roleNames[0] ?? null;
    }
    public function updateRoleBasedOnOfferings()
    {
        $hasOfferings = $this->offerings()->exists();

        if ($hasOfferings && !$this->hasRole('coordinator')) {
            $this->assignRole('coordinator');
            \Log::info("User {$this->name} (ID: {$this->id}) role updated to 'coordinator' - has offerings");
            return true;
        }

        return false;
    }
    public function getRoleDisplayNameAttribute()
    {
        $primaryRole = $this->primary_role;
        return $primaryRole ? ucfirst($primaryRole) : 'No role assigned';
    }
    public function getEffectiveRolesAttribute()
    {
        return $this->all_roles;
    }
    public function getEffectiveRolesStringAttribute()
    {
        $roles = $this->effective_roles;
        if (count($roles) === 1) {
            return ucfirst($roles[0]);
        }
        return implode(' + ', array_map('ucfirst', $roles));
    }
    
    public function getAllRolesAttribute()
    {
        return $this->getRoleNames()->toArray();
    }
    
    public function getAllRolesStringAttribute()
    {
        $roles = $this->all_roles;
        if (empty($roles)) {
            return 'No roles assigned';
        }
        if (count($roles) === 1) {
            return ucfirst($roles[0]);
        }
        return implode(' + ', array_map('ucfirst', $roles));
    }
    
    public function assignRoles($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $roles = array_values(array_unique(array_filter($roles)));

        $this->syncRoles($roles);
    }

    public function scopeWithAnyRole(Builder $query, array $roles): Builder
    {
        return $query->whereHas('roles', function (Builder $roleQuery) use ($roles) {
            $roleQuery->whereIn('name', $roles);
        });
    }

    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('roles', function (Builder $roleQuery) use ($role) {
            $roleQuery->where('name', $role);
        });
    }

    public function getFormattedNameAttribute(): string
    {
        $parts = array_filter([
            $this->name_prefix,
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ], fn ($value) => filled($value));

        if (!empty($parts)) {
            return trim(implode(' ', $parts));
        }

        return (string) $this->name;
    }
}
