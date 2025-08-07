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
        // Get student from either Auth or session
        if (Auth::check()) {
            $student = Auth::user()->student ?? null;
        } else {
            $student = \App\Models\Student::find(session('student_id'));
        }
        
        $group = $student ? Group::whereHas('members', function($q) use ($student) {
            $q->where('group_members.student_id', $student->id);
        })->with(['adviser', 'members', 'adviserInvitations.faculty'])->first() : null;
        
        // Get available faculty for adviser invitation
        $availableFaculty = User::whereIn('role', ['adviser', 'panelist'])
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
        return view('student.group.create');
    }

    public function store(Request $request)
    {
        // Debug: Log the request data
        \Log::info('Group creation request received', $request->all());
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'adviser_id' => 'required|exists:users,id',
            'adviser_message' => 'nullable|string|max:500',
            'members' => 'nullable|array|max:2', // Max 2 additional members (3 total including leader)
            'members.*' => 'exists:students,id',
        ]);
        
        // Get student from either Auth or session
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
        
        // Debug: Log user and student information
        \Log::info('User info', $userInfo);
        
        if (!$student) {
            \Log::error('Student record not found for user', $userInfo);
            return back()->with('error', 'Student record not found. Please contact administrator.');
        }
        
        // Verify the selected adviser is actually a faculty member
        $adviser = User::where('id', $request->adviser_id)
                      ->whereIn('role', ['adviser', 'panelist'])
                      ->first();
        
        // Debug: Log adviser verification
        \Log::info('Adviser verification', [
            'requested_adviser_id' => $request->adviser_id,
            'adviser_found' => $adviser ? 'yes' : 'no',
            'adviser_role' => $adviser ? $adviser->role : null
        ]);
        
        if (!$adviser) {
            \Log::error('Invalid adviser selected', ['adviser_id' => $request->adviser_id]);
            return back()->with('error', 'Selected adviser is not a valid faculty member.');
        }
        
        try {
            $group = Group::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);
            
            \Log::info('Group created successfully', ['group_id' => $group->id]);
            
            // Add the student as group leader
            $group->members()->attach($student->id, ['role' => 'leader']);
            
            // Add selected members (max 2 additional members for 3 total)
            if ($request->has('members') && is_array($request->members)) {
                $memberCount = 0;
                foreach ($request->members as $memberId) {
                    // Check if student is not already in another group
                    $existingGroup = \App\Models\Group::whereHas('members', function($q) use ($memberId) {
                        $q->where('group_members.student_id', $memberId);
                    })->first();
                    
                    if (!$existingGroup && $memberCount < 2) {
                        $group->members()->attach($memberId, ['role' => 'member']);
                        $memberCount++;
                    }
                }
            }
            
            // Create adviser invitation
            \App\Models\AdviserInvitation::create([
                'group_id' => $group->id,
                'faculty_id' => $request->adviser_id,
                'message' => $request->adviser_message,
                'status' => 'pending',
            ]);
            
            // Create notification for faculty
            \App\Models\Notification::create([
                'title' => 'New Adviser Invitation',
                'description' => 'You have received an adviser invitation from group: ' . $group->name,
                'role' => 'adviser',
            ]);
            
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
        // Get student from either Auth or session
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
        
        // Get student from either Auth or session
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

        // Get student from either Auth or session
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

        // Check if faculty is already invited
        $existingInvitation = \App\Models\AdviserInvitation::where('group_id', $group->id)
            ->where('faculty_id', $request->faculty_id)
            ->where('status', 'pending')
            ->first();

        if ($existingInvitation) {
            return back()->with('error', 'This faculty member has already been invited.');
        }

        // Create invitation
        \App\Models\AdviserInvitation::create([
            'group_id' => $group->id,
            'faculty_id' => $request->faculty_id,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        // Create notification for faculty
        \App\Models\Notification::create([
            'title' => 'New Adviser Invitation',
            'description' => 'You have received an adviser invitation from group: ' . $group->name,
            'role' => 'adviser',
        ]);

        return back()->with('success', 'Adviser invitation sent successfully!');
    }

    public function addMember(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        // Get student from either Auth or session
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

        // Check if group has reached maximum members (3)
        if ($group->members()->count() >= 3) {
            return back()->with('error', 'Group has reached the maximum of 3 members.');
        }

        // Check if student is already a member
        if ($group->members()->where('group_members.student_id', $request->student_id)->exists()) {
            return back()->with('error', 'Student is already a member of this group.');
        }

        // Add student to group
        $group->members()->attach($request->student_id, ['role' => 'member']);

        return back()->with('success', 'Member added successfully!');
    }

    public function removeMember(Request $request, $memberId)
    {
        // Get student from either Auth or session
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

        // Remove member from group
        $group->members()->detach($memberId);

        return back()->with('success', 'Member removed successfully!');
    }
}
