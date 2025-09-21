<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
class StudentGroupController extends Controller
{
    /**
     * Get available students for group invitation based on offering and semester
     * This method ensures consistent filtering for both create and existing groups
     */
    private function getAvailableStudentsForInvitation($offering, $excludeStudentIds = [], $excludePendingInvitations = [])
    {
        $query = \App\Models\Student::whereNotIn('student_id', $excludeStudentIds)
            ->whereNotIn('student_id', $excludePendingInvitations)
            ->where('semester', $offering->academicTerm->semester)
            ->whereHas('offerings', function($query) use ($offering) {
                $query->where('offering_id', $offering->id);
            })
            ->whereDoesntHave('groups', function($query) use ($offering) {
                $query->where('academic_term_id', $offering->academic_term_id);
            });
        
        return $query->get();
    }
    public function show()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->with(['adviser', 'members', 'adviserInvitations.faculty', 'offering'])->first() : null;
        
        $availableFaculty = User::whereIn('role', ['adviser', 'panelist', 'teacher'])
            ->whereDoesntHave('adviserInvitations', function($q) use ($group) {
                if ($group) {
                    $q->where('group_id', $group->id)->where('status', 'pending');
                }
            })
            ->get();
        
        // Get available students for invitation using consistent filtering
        $availableStudents = collect();
        if ($group && $group->offering) {
            $excludeStudentIds = $group->members->pluck('student_id')->toArray();
            $excludePendingInvitations = $group->groupInvitations()->where('status', 'pending')->pluck('student_id')->toArray();
            $availableStudents = $this->getAvailableStudentsForInvitation(
                $group->offering, 
                $excludeStudentIds, 
                $excludePendingInvitations
            );
        }
        
        return view('student.group.show', compact('group', 'availableFaculty', 'availableStudents'));
    }
    public function create()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student record not found. Please contact administrator.');
        }
        
        // Check if student is enrolled in any offering
        $offering = $student->getCurrentOffering();
        if (!$offering) {
            return redirect()->route('student.dashboard')->with('error', 'You must be enrolled in a capstone offering before creating a group. Please contact your coordinator to get enrolled.');
        }
        
        // Get available students for invitation using consistent filtering
        $availableStudents = $this->getAvailableStudentsForInvitation(
            $offering, 
            [$student->student_id], // Exclude the current student (group leader)
            [] // No pending invitations to exclude for new groups
        );
        
        return view('student.group.create', compact('offering', 'availableStudents'));
    }
    public function store(Request $request)
    {
        \Log::info('Group creation request received', $request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'adviser_id' => 'required|exists:users,id',
            'adviser_message' => 'nullable|string|max:500',
            'members' => 'nullable|array|max:2', // Max 2 additional members (3 total including leader) - optional
        ]);
        
        // Validate members only if they are provided and not empty
        if ($request->has('members') && is_array($request->members) && !empty(array_filter($request->members))) {
            $request->validate([
                'members.*' => 'required|exists:students,student_id',
            ]);
        }
        
        // Get student and validate enrollment
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
            $userInfo = [
                'user_id' => $studentAccount->student_id,
                'user_email' => $studentAccount->email,
                'student_exists' => $student ? 'yes' : 'no',
                'student_id' => $student ? $student->student_id : null
            ];
        } else {
            $student = null;
            $userInfo = [
                'user_id' => null,
                'user_email' => null,
                'student_exists' => 'no',
                'student_id' => null
            ];
        }
        \Log::info('User info', $userInfo);
        if (!$student) {
            \Log::error('Student record not found for user', $userInfo);
            return back()->with('error', 'Student record not found. Please contact administrator.');
        }
        
        // Validate that student is enrolled in an offering
        $offering = $student->getCurrentOffering();
        if (!$offering) {
            \Log::error('Student not enrolled in any offering', ['student_id' => $student->student_id]);
            return back()->with('error', 'You must be enrolled in a capstone offering before creating a group. Please contact your coordinator to get enrolled.');
        }
        // Get current active term for adviser validation
        $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
        
        $adviser = User::where('id', $request->adviser_id)
                      ->where('role', 'adviser')
                      ->where('semester', $activeTerm ? $activeTerm->semester : null)
                      ->first();
        \Log::info('Adviser verification', [
            'requested_adviser_id' => $request->adviser_id,
            'adviser_found' => $adviser ? 'yes' : 'no',
            'adviser_role' => $adviser ? $adviser->role : null
        ]);
        if (!$adviser) {
            \Log::error('Invalid adviser selected', ['adviser_id' => $request->adviser_id]);
            return back()->with('error', 'Selected adviser is not available for the current semester. Please select an adviser from the current term.');
        }
        
        // Validate that all group members are enrolled in the same offering
        if ($request->has('members') && is_array($request->members) && !empty(array_filter($request->members))) {
            foreach ($request->members as $memberId) {
                if (empty($memberId)) continue; // Skip empty values
                
                $member = \App\Models\Student::where('student_id', $memberId)->first();
                if (!$member) {
                    return back()->with('error', "Student with ID {$memberId} not found.");
                }
                
                $memberOffering = $member->getCurrentOffering();
                if (!$memberOffering || $memberOffering->id !== $offering->id) {
                    return back()->with('error', "Student {$member->name} is not enrolled in the same capstone offering as you. All group members must be enrolled in {$offering->offer_code} ({$offering->subject_code}).");
                }
            }
        }
        try {
            $group = Group::create([
                'name' => $request->name,
                'description' => $request->description,
                'offering_id' => $offering->id,
                'academic_term_id' => $offering->academic_term_id,
            ]);
            \Log::info('Group created successfully', ['group_id' => $group->id]);
            $group->members()->attach($student->student_id, ['role' => 'leader']);
            
            // Send invitations to selected members instead of directly adding them
            if ($request->has('members') && is_array($request->members) && !empty(array_filter($request->members))) {
                foreach ($request->members as $memberId) {
                    if (empty($memberId)) continue; // Skip empty values
                    
                    $existingGroup = \App\Models\Group::whereHas('members', function($q) use ($memberId) {
                        $q->where('group_members.student_id', $memberId);
                    })->first();
                    
                    if (!$existingGroup) {
                        // Create group invitation
                        \App\Models\GroupInvitation::create([
                            'group_id' => $group->id,
                            'student_id' => $memberId,
                            'invited_by_student_id' => $student->student_id,
                            'message' => "You have been invited to join the group '{$group->name}' for {$offering->subject_code} - {$offering->subject_title}.",
                            'status' => 'pending',
                        ]);
                    }
                }
            }
            \App\Models\AdviserInvitation::create([
                'group_id' => $group->id,
                'faculty_id' => $request->adviser_id,
                'message' => $request->adviser_message,
                'status' => 'pending',
            ]);
            $faculty = User::find($request->adviser_id);
            if ($faculty) {
                \App\Services\NotificationService::newAdviserInvitation($faculty, $group->name);
            }
            \Log::info('Group creation completed successfully', ['group_id' => $group->id]);
            $invitationCount = count($request->members ?? []);
            $message = 'Group created successfully! Adviser invitation has been sent.';
            if ($invitationCount > 0) {
                $message .= " {$invitationCount} member invitation(s) have been sent.";
            } else {
                $message .= " You can invite members later from your group page.";
            }
            return redirect()->route('student.group')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error creating group', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'An error occurred while creating the group. Please try again.');
        }
    }
    public function edit()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->with(['adviser', 'members', 'adviserInvitations.faculty', 'offering'])->first() : null;
        
        // Get available students for invitation using consistent filtering
        $availableStudents = collect();
        if ($group && $group->offering) {
            $excludeStudentIds = $group->members->pluck('student_id')->toArray();
            $excludePendingInvitations = $group->groupInvitations()->where('status', 'pending')->pluck('student_id')->toArray();
            $availableStudents = $this->getAvailableStudentsForInvitation(
                $group->offering, 
                $excludeStudentIds, 
                $excludePendingInvitations
            );
        }
        
        return view('student.group.edit', compact('group', 'availableStudents'));
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first() : null;
        if (!$group) {
            return back()->with('error', 'Group not found or you do not have permission to edit it.');
        }
        try {
            $group->update([
                'name' => trim($request->name),
                'description' => trim($request->description),
            ]);
            return redirect()->route('student.group.edit')->with('success', 'Group information updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while updating the group. Please try again.');
        }
    }
    public function index()
    {
        // Get the authenticated student
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }
        
        // Get the student's group (any term)
        $studentGroup = \App\Models\Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })
        ->with(['adviser', 'members', 'offering'])
        ->first();
        
        $groups = $studentGroup ? collect([$studentGroup]) : collect();
        
        return view('student.group.index', compact('groups'));
    }
    public function inviteAdviser(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first() : null;
        if (!$group) {
            return back()->with('error', 'Group not found.');
        }
        $existingInvitation = \App\Models\AdviserInvitation::where('group_id', $group->id)
            ->where('faculty_id', $request->faculty_id)
            ->where('status', 'pending')
            ->first();
        if ($existingInvitation) {
            return back()->with('error', 'This faculty member has already been invited.');
        }
        \App\Models\AdviserInvitation::create([
            'group_id' => $group->id,
            'faculty_id' => $request->faculty_id,
            'message' => $request->message,
            'status' => 'pending',
        ]);
        $faculty = User::find($request->faculty_id);
        if ($faculty) {
            \App\Services\NotificationService::newAdviserInvitation($faculty, $group->name);
        }
        return back()->with('success', 'Adviser invitation sent successfully!');
    }
    public function inviteMember(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'message' => 'nullable|string|max:500',
        ]);
        
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->with('offering')->first() : null;
        
        if (!$group) {
            return back()->with('error', 'Group not found.');
        }
        
        if ($group->members()->count() >= 3) {
            return back()->with('error', 'Group has reached the maximum of 3 members.');
        }
        
        if ($group->members()->where('group_members.student_id', $request->student_id)->exists()) {
            return back()->with('error', 'Student is already a member of this group.');
        }
        
        // Check if invitation already exists
        $existingInvitation = \App\Models\GroupInvitation::where('group_id', $group->id)
            ->where('student_id', $request->student_id)
            ->where('status', 'pending')
            ->first();
            
        if ($existingInvitation) {
            return back()->with('error', 'An invitation has already been sent to this student.');
        }
        
        // Validate that the invited student is eligible using consistent filtering
        $invitedStudent = \App\Models\Student::where('student_id', $request->student_id)->first();
        if (!$invitedStudent) {
            return back()->with('error', 'Student not found.');
        }
        
        // Use the same validation logic as the filtering method
        if (!$group->offering) {
            return back()->with('error', 'Group does not have an offering assigned. Please contact your coordinator.');
        }
        
        // Check if student is from the same semester and offering as the group
        if ($invitedStudent->semester !== $group->offering->academicTerm->semester) {
            return back()->with('error', "Student {$invitedStudent->name} is not enrolled in the same semester as your group. Only students from {$group->offering->academicTerm->semester} can be invited.");
        }
        
        $memberOffering = $invitedStudent->getCurrentOffering();
        if (!$memberOffering || $memberOffering->id !== $group->offering->id) {
            return back()->with('error', "Student {$invitedStudent->name} is not enrolled in the same capstone offering as your group. All group members must be enrolled in {$group->offering->offer_code} ({$group->offering->subject_code}).");
        }
        
        // Check if student already has a group in the same term
        $existingGroup = \App\Models\Group::whereHas('members', function($q) use ($invitedStudent) {
            $q->where('group_members.student_id', $invitedStudent->student_id);
        })->where('academic_term_id', $group->offering->academic_term_id)->first();
        
        if ($existingGroup) {
            return back()->with('error', "Student {$invitedStudent->name} is already a member of another group in {$group->offering->academicTerm->semester}.");
        }
        
        // Create invitation
        \App\Models\GroupInvitation::create([
            'group_id' => $group->id,
            'student_id' => $request->student_id,
            'invited_by_student_id' => $student->student_id,
            'message' => $request->message,
            'status' => 'pending',
        ]);
        
        return back()->with('success', 'Invitation sent successfully! The student will be notified and can accept or decline the invitation.');
    }
    
    public function acceptInvitation(Request $request, $invitationId)
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }
        
        $invitation = \App\Models\GroupInvitation::where('id', $invitationId)
            ->where('student_id', $student->student_id)
            ->first();
            
        if (!$invitation) {
            return back()->with('error', 'Invitation not found.');
        }
        
        if ($invitation->status !== 'pending') {
            return back()->with('error', 'Invitation has already been processed.');
        }
        
        $group = $invitation->group;
        
        // Check if group still has space
        if ($group->members()->count() >= 3) {
            $invitation->decline();
            return back()->with('error', 'Group is now full. Invitation declined.');
        }
        
        // Check if student is already in another group (but not necessarily this one)
        $existingGroup = Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first();
        
        if ($existingGroup && $existingGroup->id !== $group->id) {
            $invitation->decline();
            return back()->with('error', 'You are already a member of another group.');
        }
        
        // If student is already in this group, just accept the invitation without adding again
        if ($group->members()->where('group_members.student_id', $student->student_id)->exists()) {
            $invitation->accept();
            return back()->with('success', 'Invitation accepted! You are already a member of this group.');
        }
        
        try {
            // Add student to group first, then accept invitation
            $group->members()->attach($student->student_id, ['role' => 'member']);
            $invitation->accept();
            
            return back()->with('success', 'Invitation accepted! You are now a member of the group.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle duplicate entry error - this should not happen with proper constraint
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                // If we get here, it means the student was added between our checks
                $invitation->accept();
                return back()->with('success', 'Invitation accepted! You are now a member of the group.');
            }
            
            // Re-throw other database errors
            throw $e;
        }
    }
    
    public function declineInvitation(Request $request, $invitationId)
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }
        
        $invitation = \App\Models\GroupInvitation::where('id', $invitationId)
            ->where('student_id', $student->student_id)
            ->where('status', 'pending')
            ->first();
            
        if (!$invitation) {
            return back()->with('error', 'Invitation not found or already processed.');
        }
        
        $invitation->decline();
        
        return back()->with('success', 'Invitation declined.');
    }
    
    public function invitations()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }
        
        // Check if student already has a group
        if ($student->groups()->exists()) {
            return redirect()->route('student.group')->with('info', 'You are already in a group. Group invitations are only available for students without a group.');
        }
        
        $invitations = \App\Models\GroupInvitation::where('student_id', $student->student_id)
            ->where('status', 'pending')
            ->with(['group', 'group.offering', 'invitedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('student.group.invitations', compact('invitations'));
    }
    public function removeMember(Request $request, $memberId)
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }
        
        $group = Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first();
        
        if (!$group) {
            return back()->with('error', 'Group not found.');
        }
        
        // Check if the current student is the group leader
        $isLeader = $group->members()
            ->where('group_members.student_id', $student->student_id)
            ->where('group_members.role', 'leader')
            ->exists();
            
        if (!$isLeader) {
            return back()->with('error', 'Only the group leader can remove members.');
        }
        
        // Check if trying to remove the leader
        $memberToRemove = $group->members()
            ->where('group_members.student_id', $memberId)
            ->first();
            
        if ($memberToRemove && $memberToRemove->pivot->role === 'leader') {
            return back()->with('error', 'The group leader cannot be removed.');
        }
        
        // Remove the member
        $group->members()->detach($memberId);
        
        return back()->with('success', 'Member removed successfully!');
    }
    public function requestDefense(Request $request)
    {
        $request->validate([
            'defense_type' => 'required|in:proposal,60_percent,100_percent',
            'message' => 'nullable|string|max:500',
        ]);
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first() : null;
        if (!$group) {
            return back()->with('error', 'Group not found.');
        }
        if (!$group->adviser_id) {
            return back()->with('error', 'Your group must have an assigned adviser before requesting a defense.');
        }
        if ($request->defense_type === '60_percent' && $group->overall_progress_percentage < 60) {
            return back()->with('error', 'Your group must reach 60% progress before requesting a 60% defense.');
        }
        if ($request->defense_type === '100_percent' && $group->overall_progress_percentage < 100) {
            return back()->with('error', 'Your group must reach 100% progress before requesting a final defense.');
        }
        $existingRequest = \App\Models\DefenseRequest::where('group_id', $group->id)
            ->where('defense_type', $request->defense_type)
            ->where('status', 'pending')
            ->first();
        if ($existingRequest) {
            return back()->with('error', 'You already have a pending request for this defense type.');
        }
        try {
            \App\Models\DefenseRequest::create([
                'group_id' => $group->id,
                'defense_type' => $request->defense_type,
                'status' => 'pending',
                'student_message' => $request->message,
                'requested_at' => now(),
            ]);
            \App\Services\NotificationService::notifyCoordinators(
                'New Defense Request',
                'Group ' . $group->name . ' has requested a ' . $request->defense_type . ' defense',
                route('coordinator.defense-requests.index')
            );
            return back()->with('success', 'Defense request submitted successfully! Coordinator will review and schedule your defense.');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while submitting your defense request.');
        }
    }

    public function cancelInvitation(Request $request, $invitationId)
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
        } else {
            $student = null;
        }
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }
        
        // Find the invitation
        $invitation = \App\Models\GroupInvitation::findOrFail($invitationId);
        
        // Check if the student is the one who sent the invitation
        if ($invitation->invited_by_student_id !== $student->student_id) {
            return back()->with('error', 'You can only cancel invitations that you sent.');
        }
        
        // Check if the invitation is still pending
        if ($invitation->status !== 'pending') {
            return back()->with('error', 'You can only cancel pending invitations.');
        }
        
        // Delete the invitation
        $invitation->delete();
        
        return back()->with('success', 'Invitation cancelled successfully.');
    }
}
