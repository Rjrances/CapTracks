<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    use Notifiable;
    protected $fillable = [
        'faculty_id',
        'name',
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
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role', 'id', 'name')
                    ->withTimestamps();
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
        return $this->hasMany(\App\Models\AdviserInvitation::class, 'faculty_id');
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
    public function hasRole($role): bool
    {
        return $this->role === $role;
    }
    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }
        return in_array($this->role, $roles);
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
    public function getCoordinatedOfferings($activeTerm = null)
    {
        $query = $this->offerings()->with('academicTerm');
        
        if ($activeTerm) {
            $query->where('academic_term_id', $activeTerm->id);
        }
        
        return $query->get();
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
        return $this->role;
    }
    public function updateRoleBasedOnOfferings()
    {
        $hasOfferings = $this->offerings()->exists();
        $currentRole = $this->role;
        
        if ($hasOfferings && $this->role !== 'coordinator') {
            $oldRole = $this->role;
            $this->role = 'coordinator';
            $this->save();
            \Log::info("User {$this->name} (ID: {$this->id}) role updated from '{$oldRole}' to 'coordinator' - has offerings");
            return true;
        }
        
        return false;
    }
    public function getRoleDisplayNameAttribute()
    {
        return ucfirst($this->role);
    }
    public function getEffectiveRolesAttribute()
    {
        return [$this->role];
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
        $assignedRoles = $this->roles()->pluck('name')->toArray();
        $primaryRole = $this->role ? [$this->role] : [];
        return array_unique(array_merge($primaryRole, $assignedRoles));
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
        
        $this->roles()->detach();
        
        foreach ($roles as $role) {
            $roleModel = Role::where('name', $role)->first();
            if ($roleModel) {
                $this->roles()->attach($roleModel->id);
            }
        }
        
        if (!empty($roles)) {
            $this->update(['role' => $roles[0]]);
        }
    }
}
