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
        'department',     // Department instead of course
        'role',           // Role instead of position
        'semester',       // Semester when faculty was uploaded
    ];

    // Set primary key
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
        // Check both the single role field and the many-to-many relationship
        return $this->role === $role || $this->roles()->where('name', $role)->exists();
    }
    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }
        // Check both the single role field and the many-to-many relationship
        return in_array($this->role, $roles) || $this->roles()->whereIn('name', $roles)->exists();
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
        return false; // Students are in separate table
    }
    public function getPrimaryRoleAttribute()
    {
        return $this->role;
    }
    public function updateRoleBasedOnOfferings()
    {
        $hasOfferings = $this->offerings()->exists();
        $currentRole = $this->role;
        \Log::info("Checking role for user {$this->name} (ID: {$this->id}): current role = '{$currentRole}', has offerings = " . ($hasOfferings ? 'true' : 'false'));
        if ($hasOfferings && $this->role !== 'coordinator') {
            $oldRole = $this->role;
            $this->role = 'coordinator';
            $this->save();
            \Log::info("User {$this->name} (ID: {$this->id}) role updated from '{$oldRole}' to 'coordinator' - has offerings");
            return true;
        } elseif (!$hasOfferings && $this->role === 'coordinator') {
            $this->role = 'teacher';
            $this->save();
            \Log::info("User {$this->name} (ID: {$this->id}) role reverted from 'coordinator' to 'teacher' - no offerings");
            return true;
        }
        \Log::info("User {$this->name} (ID: {$this->id}) no role change needed: current role = '{$currentRole}', has offerings = " . ($hasOfferings ? 'true' : 'false'));
        return false; // No change needed
    }
    public function updateRoleBasedOnAdviserAssignments()
    {
        $hasAdviserGroups = \App\Models\Group::where('faculty_id', $this->faculty_id)->exists();
        $currentRole = $this->role;
        \Log::info("Checking adviser role for user {$this->name} (ID: {$this->id}): current role = '{$currentRole}', has adviser groups = " . ($hasAdviserGroups ? 'true' : 'false'));
        if ($hasAdviserGroups && $this->role === 'teacher') {
            $oldRole = $this->role;
            $this->role = 'adviser';
            $this->save();
            \Log::info("User {$this->name} (ID: {$this->id}) role updated from '{$oldRole}' to 'adviser' - has adviser groups");
            return true;
        } elseif (!$hasAdviserGroups && $this->role === 'adviser') {
            $this->role = 'teacher';
            $this->save();
            \Log::info("User {$this->name} (ID: {$this->id}) role reverted from 'adviser' to 'teacher' - no adviser groups");
            return true;
        }
        \Log::info("User {$this->name} (ID: {$this->id}) no adviser role change needed: current role = '{$currentRole}', has adviser groups = " . ($hasAdviserGroups ? 'true' : 'false'));
        return false; // No change needed
    }
    public function getRoleDisplayNameAttribute()
    {
        if ($this->role === 'coordinator' && $this->offerings()->exists()) {
            return 'Coordinator';
        }
        return ucfirst($this->role);
    }
    public function getEffectiveRolesAttribute()
    {
        $roles = [$this->role];
        if ($this->offerings()->exists() && !in_array('coordinator', $roles)) {
            $roles[] = 'coordinator';
        }
        if (\App\Models\Group::where('faculty_id', $this->faculty_id)->exists() && !in_array('adviser', $roles)) {
            $roles[] = 'adviser';
        }
        return array_unique($roles);
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
        
        // Clear existing roles
        $this->roles()->detach();
        
        // Assign new roles
        foreach ($roles as $role) {
            $roleModel = Role::where('name', $role)->first();
            if ($roleModel) {
                $this->roles()->attach($roleModel->id);
            }
        }
        
        // Update primary role to the first assigned role
        if (!empty($roles)) {
            $this->update(['role' => $roles[0]]);
        }
    }
}
