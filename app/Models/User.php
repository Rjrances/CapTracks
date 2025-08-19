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
        'role',           // Role instead of position
        'password',
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
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role', 'id', 'name')
                    ->withTimestamps();
    }

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

    /**
     * ================================
     *        ROLE CHECK HELPERS
     * ================================
     */
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

    public function getCoordinatedOfferings()
    {
        return $this->offerings()->with('academicTerm')->get();
    }

    public function canBeAdviserForGroup($groupId): bool
    {
        // Get the group's offering
        $group = \App\Models\Group::find($groupId);
        if (!$group) return false;

        // Check if this user coordinates the group's offering
        $coordinatedOfferingIds = $this->offerings()->pluck('id')->toArray();
        
        // Cannot be adviser for groups in offerings they coordinate
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

    /**
     * Check if user should be coordinator based on offerings
     * and update role accordingly
     */
    public function updateRoleBasedOnOfferings()
    {
        $hasOfferings = $this->offerings()->exists();
        $currentRole = $this->role;
        
        \Log::info("Checking role for user {$this->name} (ID: {$this->id}): current role = '{$currentRole}', has offerings = " . ($hasOfferings ? 'true' : 'false'));
        
        if ($hasOfferings && $this->role !== 'coordinator') {
            // Has offerings but not coordinator role
            $oldRole = $this->role;
            $this->role = 'coordinator';
            $this->save();
            \Log::info("User {$this->name} (ID: {$this->id}) role updated from '{$oldRole}' to 'coordinator' - has offerings");
            return true;
        } elseif (!$hasOfferings && $this->role === 'coordinator') {
            // No offerings but has coordinator role
            $this->role = 'teacher';
            $this->save();
            \Log::info("User {$this->name} (ID: {$this->id}) role reverted from 'coordinator' to 'teacher' - no offerings");
            return true;
        }
        
        \Log::info("User {$this->name} (ID: {$this->id}) no role change needed: current role = '{$currentRole}', has offerings = " . ($hasOfferings ? 'true' : 'false'));
        return false; // No change needed
    }

    /**
     * Check if user should be adviser based on group assignments
     * and update role accordingly
     */
    public function updateRoleBasedOnAdviserAssignments()
    {
        $hasAdviserGroups = \App\Models\Group::where('adviser_id', $this->id)->exists();
        $currentRole = $this->role;
        
        \Log::info("Checking adviser role for user {$this->name} (ID: {$this->id}): current role = '{$currentRole}', has adviser groups = " . ($hasAdviserGroups ? 'true' : 'false'));
        
        if ($hasAdviserGroups && $this->role === 'teacher') {
            // Has adviser groups but still has 'teacher' role
            $oldRole = $this->role;
            $this->role = 'adviser';
            $this->save();
            \Log::info("User {$this->name} (ID: {$this->id}) role updated from '{$oldRole}' to 'adviser' - has adviser groups");
            return true;
        } elseif (!$hasAdviserGroups && $this->role === 'adviser') {
            // No adviser groups but still has 'adviser' role
            $this->role = 'teacher';
            $this->save();
            \Log::info("User {$this->name} (ID: {$this->id}) role reverted from 'adviser' to 'teacher' - no adviser groups");
            return true;
        }
        
        \Log::info("User {$this->name} (ID: {$this->id}) no adviser role change needed: current role = '{$currentRole}', has adviser groups = " . ($hasAdviserGroups ? 'true' : 'false'));
        return false; // No change needed
    }

    /**
     * Get the appropriate role display name
     */
    public function getRoleDisplayNameAttribute()
    {
        if ($this->role === 'coordinator' && $this->offerings()->exists()) {
            return 'Coordinator';
        }
        
        return ucfirst($this->role);
    }

    /**
     * Get all roles this user effectively has (including derived roles)
     */
    public function getEffectiveRolesAttribute()
    {
        $roles = [$this->role];
        
        // If they have offerings, they're effectively a coordinator
        if ($this->offerings()->exists() && !in_array('coordinator', $roles)) {
            $roles[] = 'coordinator';
        }
        
        // If they're an adviser to any groups, they're effectively an adviser
        if (\App\Models\Group::where('adviser_id', $this->id)->exists() && !in_array('adviser', $roles)) {
            $roles[] = 'adviser';
        }
        
        return array_unique($roles);
    }

    /**
     * Get a formatted string of all effective roles
     */
    public function getEffectiveRolesStringAttribute()
    {
        $roles = $this->effective_roles;
        if (count($roles) === 1) {
            return ucfirst($roles[0]);
        }
        
        return implode(' + ', array_map('ucfirst', $roles));
    }
}
