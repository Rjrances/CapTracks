<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;

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
            'teacher' => [
                'name' => 'Teacher',
                'description' => 'General faculty member available for assignments',
                'permissions' => ['View courses', 'Be assigned as adviser', 'Be assigned to defense panels']
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

        // Get users for each role using the new direct role column
        foreach ($roles as $roleKey => &$role) {
            if ($roleKey === 'student') {
                // Students are handled separately
                $role['user_count'] = Student::count();
                $role['users'] = collect();
                continue;
            }
            
            $role['user_count'] = User::where('role', $roleKey)->count();
            $role['users'] = User::where('role', $roleKey)
                ->select('id', 'name', 'email', 'school_id', 'department', 'role')
                ->orderBy('name')
                ->get();
        }

        // Get all users for role assignment
        $allUsers = User::select('id', 'name', 'email', 'school_id', 'department', 'role')
            ->orderBy('name')
            ->get();

        return view('chairperson.roles.index', compact('roles', 'allUsers'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'in:chairperson,coordinator,teacher,adviser,panelist',
        ]);

        try {
            // Update the user's role (take the first role if multiple are selected)
            $newRole = $request->roles[0] ?? 'teacher';
            $user->update(['role' => $newRole]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User role updated successfully.'
                ]);
            }

            return redirect()->back()->with('success', 'User role updated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating role: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error updating role: ' . $e->getMessage());
        }
    }
}
