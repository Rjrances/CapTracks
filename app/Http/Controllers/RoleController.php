<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
class RoleController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort', 'faculty_id');
        $sortDirection = $request->get('direction', 'asc');
        
        // Get active term for filtering
        $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
        
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
        
        // Get users with their roles for role assignment table
        // Try to load roles relationship, but don't fail if it doesn't work
        try {
            $allUsers = User::with('roles')
                ->select('id', 'name', 'email', 'faculty_id', 'department', 'role')
                ->when($activeTerm, function($query) use ($activeTerm) {
                    return $query->where('semester', $activeTerm->semester);
                })
                ->orderBy($sortBy, $sortDirection)
                ->paginate(20);
        } catch (\Exception $e) {
            \Log::warning('Roles relationship failed in RoleController: ' . $e->getMessage());
            $allUsers = User::select('id', 'name', 'email', 'faculty_id', 'department', 'role')
                ->when($activeTerm, function($query) use ($activeTerm) {
                    return $query->where('semester', $activeTerm->semester);
                })
                ->orderBy($sortBy, $sortDirection)
                ->paginate(20);
        }
            
        // Count users by role for active semester (excluding students)
        foreach ($roles as $roleKey => &$role) {
            if ($roleKey === 'student') {
                // Skip student count - only show faculty roles
                $role['user_count'] = 0;
                continue;
            }
            
            // Count faculty with this role for active semester
            $query = User::whereIn('role', ['chairperson', 'coordinator', 'adviser', 'panelist', 'teacher']);
            
            if ($activeTerm) {
                $query->where('semester', $activeTerm->semester);
            }
            
            $role['user_count'] = $query->where('role', $roleKey)->count();
        }
        
        return view('chairperson.roles.index', compact('roles', 'allUsers', 'activeTerm', 'sortBy', 'sortDirection'));
    }
    public function update(Request $request, $faculty_id)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'in:chairperson,coordinator,teacher,adviser,panelist',
        ]);
        
        try {
            // Get active term for filtering
            $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
            
            // Find user by faculty_id and active semester
            $user = User::where('faculty_id', $faculty_id)
                ->when($activeTerm, function($query) use ($activeTerm) {
                    return $query->where('semester', $activeTerm->semester);
                })
                ->firstOrFail();
            
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
