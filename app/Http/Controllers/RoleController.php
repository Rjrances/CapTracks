<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
class RoleController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        
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
        // Get users with their roles
        $allUsers = User::with('roles')
            ->select('id', 'name', 'email', 'faculty_id', 'department', 'role')
            ->orderBy($sortBy, $sortDirection)
            ->get();
            
        // Count users by role (including multiple roles)
        foreach ($roles as $roleKey => &$role) {
            if ($roleKey === 'student') {
                $role['user_count'] = Student::count();
                $role['users'] = collect();
                continue;
            }
            
            // Count users who have this role (either as primary or assigned)
            $role['user_count'] = $allUsers->filter(function($user) use ($roleKey) {
                return $user->hasRole($roleKey);
            })->count();
            
            // Get users with this role
            $role['users'] = $allUsers->filter(function($user) use ($roleKey) {
                return $user->hasRole($roleKey);
            });
        }
        return view('chairperson.roles.index', compact('roles', 'allUsers', 'sortBy', 'sortDirection'));
    }
    public function update(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'in:chairperson,coordinator,teacher,adviser,panelist',
        ]);
        
        try {
            // Use the new assignRoles method
            $user->assignRoles($request->roles);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User roles updated successfully.',
                    'user_roles' => $user->all_roles_string
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
