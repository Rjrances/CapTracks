<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        // Define available roles in the system
        $roles = [
            'chairperson' => [
                'name' => 'Chairperson',
                'description' => 'Department chair with full administrative access',
                'permissions' => ['Manage academic terms', 'Create offerings', 'Assign faculty', 'Schedule defenses', 'Manage roles']
            ],
            'coordinator' => [
                'name' => 'Coordinator',
                'description' => 'Course coordinator with group and milestone management',
                'permissions' => ['Manage groups', 'Create milestones', 'Track progress', 'Validate 60% defense readiness']
            ],
            'adviser' => [
                'name' => 'Adviser',
                'description' => 'Faculty adviser for student groups',
                'permissions' => ['Guide student groups', 'Review submissions', 'Provide feedback', 'Monitor progress']
            ],
            'panelist' => [
                'name' => 'Panelist',
                'description' => 'Defense panel member',
                'permissions' => ['Participate in defenses', 'Evaluate projects', 'Provide assessment']
            ],
            'student' => [
                'name' => 'Student',
                'description' => 'Student working on capstone project',
                'permissions' => ['Submit projects', 'Track milestones', 'Join groups', 'View progress']
            ]
        ];

        // Get users for each role using the new pivot table
        foreach ($roles as $roleKey => &$role) {
            if ($roleKey === 'student') {
                // Students are handled separately
                $role['user_count'] = 0;
                $role['users'] = collect();
                continue;
            }
            
            $role['user_count'] = \DB::table('user_roles')->where('role', $roleKey)->count();
            $userIds = \DB::table('user_roles')->where('role', $roleKey)->pluck('user_id');
            $role['users'] = User::whereIn('id', $userIds)
                ->select('id', 'name', 'email', 'school_id', 'department', 'position')
                ->orderBy('name')
                ->get();
        }

        // Get all users for role assignment
        $allUsers = User::select('id', 'name', 'email', 'school_id', 'department', 'position')
            ->orderBy('name')
            ->get();

        // Get current roles for each user
        foreach ($allUsers as $user) {
            $user->currentRoles = $user->roles->pluck('name')->toArray();
        }

        return view('chairperson.roles.index', compact('roles', 'allUsers'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'in:chairperson,coordinator,adviser,panelist',
        ]);

        try {
            // Remove all existing roles
            $user->roles()->detach();
            
            // Add new roles
            foreach ($request->roles as $role) {
                $roleModel = Role::where('name', $role)->first();
                if ($roleModel) {
                    $user->roles()->attach($roleModel->id);
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User roles updated successfully.'
                ]);
            }

            return redirect()->back()->with('success', 'User roles updated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating roles: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error updating roles: ' . $e->getMessage());
        }
    }
}
