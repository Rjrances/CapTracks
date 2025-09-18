<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
class StudentGroupController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            $student = Auth::user()->student ?? null;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->id);
        })->with(['adviser', 'members', 'adviserInvitations.faculty'])->first() : null;
        $availableFaculty = User::whereIn('role', ['adviser', 'panelist', 'teacher'])
            ->whereDoesntHave('adviserInvitations', function($q) use ($group) {
                if ($group) {
                    $q->where('group_id', $group->id)->where('status', 'pending');
                }
            })
            ->get();
        return view('student.group.show', compact('group', 'availableFaculty'));
    }
    public function create()
    {
        if (Auth::check()) {
            $student = Auth::user()->student ?? null;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student record not found. Please contact administrator.');
        }
        
        // Check if student is enrolled in any offering
        $offering = $student->getCurrentOffering();
        if (!$offering) {
            return redirect()->route('student.dashboard')->with('error', 'You must be enrolled in a capstone offering before creating a group. Please contact your coordinator to get enrolled.');
        }
        
        return view('student.group.create', compact('offering'));
    }
    public function store(Request $request)
    {
        \Log::info('Group creation request received', $request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'adviser_id' => 'required|exists:users,id',
            'adviser_message' => 'nullable|string|max:500',
            'members' => 'nullable|array|max:2', // Max 2 additional members (3 total including leader)
            'members.*' => 'exists:students,id',
        ]);
        
        // Get student and validate enrollment
        if (Auth::check()) {
            $student = Auth::user()->student;
            $userInfo = [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'student_exists' => $student ? 'yes' : 'no',
                'student_id' => $student ? $student->id : null
            ];
        } else {
            $student = \App\Models\Student::find(session('student_id'));
            $userInfo = [
                'user_id' => session('student_id'),
                'user_email' => session('student_email'),
                'student_exists' => $student ? 'yes' : 'no',
                'student_id' => $student ? $student->id : null
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
            \Log::error('Student not enrolled in any offering', ['student_id' => $student->id]);
            return back()->with('error', 'You must be enrolled in a capstone offering before creating a group. Please contact your coordinator to get enrolled.');
        }
        $adviser = User::where('id', $request->adviser_id)
                      ->whereIn('role', ['adviser', 'panelist', 'teacher'])
                      ->first();
        \Log::info('Adviser verification', [
            'requested_adviser_id' => $request->adviser_id,
            'adviser_found' => $adviser ? 'yes' : 'no',
            'adviser_role' => $adviser ? $adviser->role : null
        ]);
        if (!$adviser) {
            \Log::error('Invalid adviser selected', ['adviser_id' => $request->adviser_id]);
            return back()->with('error', 'Selected adviser is not a valid faculty member.');
        }
        
        // Validate that all group members are enrolled in the same offering
        if ($request->has('members') && is_array($request->members)) {
            foreach ($request->members as $memberId) {
                $member = \App\Models\Student::find($memberId);
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
            $group->members()->attach($student->id, ['role' => 'leader']);
            if ($request->has('members') && is_array($request->members)) {
                $memberCount = 0;
                foreach ($request->members as $memberId) {
                    $existingGroup = \App\Models\Group::whereHas('members', function($q) use ($memberId) {
                        $q->where('group_members.student_id', $memberId);
                    })->first();
                    if (!$existingGroup && $memberCount < 2) {
                        $group->members()->attach($memberId, ['role' => 'member']);
                        $memberCount++;
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
            return redirect()->route('student.group')->with('success', 'Group created successfully! Adviser invitation has been sent.');
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
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->id);
        })->with(['adviser', 'members', 'adviserInvitations.faculty'])->first() : null;
        return view('student.group.edit', compact('group'));
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->id);
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
        $groups = \App\Models\Group::with('adviser')->get();
        return view('student.group.index', compact('groups'));
    }
    public function inviteAdviser(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->id);
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
    public function addMember(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->id);
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
        
        // Validate that the new member is enrolled in the same offering
        $newMember = \App\Models\Student::find($request->student_id);
        if (!$newMember) {
            return back()->with('error', 'Student not found.');
        }
        
        $memberOffering = $newMember->getCurrentOffering();
        if (!$memberOffering || !$group->offering || $memberOffering->id !== $group->offering->id) {
            return back()->with('error', "Student {$newMember->name} is not enrolled in the same capstone offering as your group. All group members must be enrolled in {$group->offering->offer_code} ({$group->offering->subject_code}).");
        }
        
        $group->members()->attach($request->student_id, ['role' => 'member']);
        return back()->with('success', 'Member added successfully!');
    }
    public function removeMember(Request $request, $memberId)
    {
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->id);
        })->first() : null;
        if (!$group) {
            return back()->with('error', 'Group not found.');
        }
        $group->members()->detach($memberId);
        return back()->with('success', 'Member removed successfully!');
    }
    public function requestDefense(Request $request)
    {
        $request->validate([
            'defense_type' => 'required|in:proposal,60_percent,100_percent',
            'message' => 'nullable|string|max:500',
        ]);
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->id);
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
}
