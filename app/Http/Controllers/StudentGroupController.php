<?php

namespace App\Http\Controllers;

use App\Models\AdviserInvitation;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentGroupController extends Controller
{
    private function getAvailableStudentsForInvitation($offering, $excludeStudentIds = [], $excludePendingInvitations = [])
    {
        $term = $offering->academicTerm;

        $query = \App\Models\Student::whereNotIn('student_id', $excludeStudentIds)
            ->whereNotIn('student_id', $excludePendingInvitations)
            ->when($term, fn ($q) => $q->forAcademicTerm($term))
            ->whereHas('offerings', function ($query) use ($offering) {
                $query->where('offering_id', $offering->id);
            })
            ->whereDoesntHave('groups', function ($query) use ($offering) {
                $query->where('academic_term_id', $offering->academic_term_id);
            });

        return $query->get();
    }

    private function getAuthenticatedStudent(): ?Student
    {
        if (! Auth::guard('student')->check()) {
            return null;
        }

        return Auth::guard('student')->user()->student;
    }

    public function show()
    {
        $student = $this->getAuthenticatedStudent();
        $group = $student ? Group::whereHas('members', function ($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->with(['adviser', 'members', 'adviserInvitations.faculty', 'offering'])->first() : null;

        $availableFaculty = User::withAnyRole(['adviser', 'panelist', 'teacher', 'coordinator'])
            ->when($group && $group->academicTerm, function ($query) use ($group) {
                return $query->where('academic_term_id', $group->academic_term_id);
            })
            ->whereDoesntHave('adviserInvitations', function ($q) use ($group) {
                if ($group) {
                    $q->where('group_id', $group->id)->where('status', 'pending');
                }
            })
            ->orderBy('name')
            ->get();

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
        $student = $this->getAuthenticatedStudent();

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student record not found. Please contact administrator.');
        }

        $offering = $student->getCurrentOffering();
        if (! $offering) {
            return redirect()->route('student.group')->with('error', 'You must be enrolled in a capstone offering before creating a group. Please contact your coordinator to get enrolled.');
        }

        $availableStudents = $this->getAvailableStudentsForInvitation(
            $offering,
            [$student->student_id],
            []
        );

        return view('student.group.create', compact('offering', 'availableStudents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'adviser_id' => 'required|exists:users,id',
            'adviser_message' => 'nullable|string|max:500',
            'members' => 'nullable|array|max:2',
            'members.*' => 'nullable|exists:students,student_id',
        ]);

        $student = $this->getAuthenticatedStudent();

        if (! $student) {
            return back()->with('error', 'Student record not found. Please contact administrator.');
        }

        $offering = $student->getCurrentOffering();
        if (! $offering) {
            \Log::error('Student not enrolled in any offering', ['student_id' => $student->student_id]);

            return back()->with('error', 'You must be enrolled in a capstone offering before creating a group. Please contact your coordinator to get enrolled.');
        }

        $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();

        $adviser = User::withAnyRole(['adviser', 'teacher', 'coordinator'])
            ->where('id', $request->adviser_id)
            ->where('academic_term_id', $activeTerm ? $activeTerm->id : null)
            ->first();
        if (! $adviser) {
            return back()->with('error', 'Selected adviser is not available for the current semester. Please select an adviser from the current term.');
        }

        if ($request->has('members') && is_array($request->members) && ! empty(array_filter($request->members))) {
            foreach ($request->members as $memberId) {
                if (empty($memberId)) {
                    continue;
                }

                $member = \App\Models\Student::where('student_id', $memberId)->first();
                if (! $member) {
                    return back()->with('error', "Student with ID {$memberId} not found.");
                }

                $memberOffering = $member->getCurrentOffering();
                if (! $memberOffering || $memberOffering->id !== $offering->id) {
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
            $group->members()->attach($student->student_id, ['role' => 'leader']);

            if ($request->has('members') && is_array($request->members) && ! empty(array_filter($request->members))) {
                foreach ($request->members as $memberId) {
                    if (empty($memberId)) {
                        continue;
                    }

                    $existingGroup = \App\Models\Group::whereHas('members', function ($q) use ($memberId) {
                        $q->where('group_members.student_id', $memberId);
                    })->first();

                    if (! $existingGroup) {

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
            $faculty = User::find($request->adviser_id);
            if (! $faculty) {
                return back()->with('error', 'Selected adviser not found. Please try again.');
            }

            \App\Models\AdviserInvitation::create([
                'group_id' => $group->id,
                'faculty_id' => $faculty->id,
                'message' => $request->adviser_message,
                'status' => 'pending',
            ]);

            \App\Services\NotificationService::newAdviserInvitation($faculty, $group->name);
            $invitationCount = count($request->members ?? []);
            $message = 'Group created successfully! Adviser invitation has been sent.';
            if ($invitationCount > 0) {
                $message .= " {$invitationCount} member invitation(s) have been sent.";
            } else {
                $message .= ' You can invite members later from your group page.';
            }

            return redirect()->route('student.group')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error creating group', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'An error occurred while creating the group. Please try again.');
        }
    }

    public function edit()
    {
        $student = $this->getAuthenticatedStudent();
        $group = $student ? Group::whereHas('members', function ($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->with(['adviser', 'members', 'adviserInvitations.faculty', 'offering'])->first() : null;

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

        $availableFaculty = User::withAnyRole(['adviser', 'panelist', 'teacher', 'coordinator'])
            ->where('academic_term_id', $group->academic_term_id)
            ->orderBy('name')
            ->get();

        return view('student.group.edit', compact('group', 'availableStudents', 'availableFaculty'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        $student = $this->getAuthenticatedStudent();
        $group = $student ? Group::whereHas('members', function ($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first() : null;
        if (! $group) {
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
        return redirect()->route('student.group');
    }

    public function inviteAdviser(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);
        $student = $this->getAuthenticatedStudent();
        $group = $student ? Group::whereHas('members', function ($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first() : null;
        if (! $group) {
            return back()->with('error', 'Group not found.');
        }
        if ($group->faculty_id) {
            return back()->with('error', 'This group already has an adviser.');
        }
        $existingPending = AdviserInvitation::where('group_id', $group->id)
            ->where('status', 'pending')
            ->exists();
        if ($existingPending) {
            return back()->with('error', 'You already have a pending adviser invitation. Cancel it before inviting someone else.');
        }
        $existingInvitation = AdviserInvitation::where('group_id', $group->id)
            ->where('faculty_id', $request->faculty_id)
            ->where('status', 'pending')
            ->first();
        if ($existingInvitation) {
            return back()->with('error', 'This faculty member has already been invited.');
        }
        $faculty = User::find($request->faculty_id);
        if (! $faculty) {
            return back()->with('error', 'Selected faculty member not found. Please try again.');
        }

        \App\Models\AdviserInvitation::create([
            'group_id' => $group->id,
            'faculty_id' => $faculty->id,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        \App\Services\NotificationService::newAdviserInvitation($faculty, $group->name);

        return back()->with('success', 'Adviser invitation sent successfully!');
    }

    public function inviteMember(Request $request)
    {
        $request->validate([
            'student_id' => 'nullable|exists:students,student_id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'nullable|exists:students,student_id',
            'message' => 'nullable|string|max:500',
        ]);

        $student = $this->getAuthenticatedStudent();

        $group = $student ? Group::whereHas('members', function ($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->with('offering')->first() : null;

        if (! $group) {
            return back()->with('error', 'Group not found.');
        }

        $requestedStudentIds = collect($request->input('student_ids', []))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values();

        if ($request->filled('student_id')) {
            $requestedStudentIds->push((string) $request->input('student_id'));
        }

        $requestedStudentIds = $requestedStudentIds->unique()->values();

        if ($requestedStudentIds->isEmpty()) {
            return back()->with('error', 'Please select at least one student to invite.');
        }

        $currentMembersCount = $group->members()->count();
        $pendingInvitationsCount = \App\Models\GroupInvitation::where('group_id', $group->id)
            ->where('status', 'pending')
            ->count();

        if ($currentMembersCount >= 3) {
            return back()->with('error', 'Group has reached the maximum of 3 members.');
        }

        $remainingSlots = 3 - $currentMembersCount - $pendingInvitationsCount;
        if ($remainingSlots <= 0) {
            return back()->with('error', 'No available slots. Wait for pending invitation(s) to be resolved before inviting more members.');
        }

        if ($requestedStudentIds->count() > $remainingSlots) {
            return back()->with('error', "You can only invite {$remainingSlots} more member(s).");
        }

        if (! $group->offering) {
            return back()->with('error', 'Group does not have an offering assigned. Please contact your coordinator.');
        }

        $group->offering->loadMissing('academicTerm');
        $offeringTerm = $group->offering->academicTerm;

        foreach ($requestedStudentIds as $studentId) {
            if ($group->members()->where('group_members.student_id', $studentId)->exists()) {
                return back()->with('error', 'One or more selected students are already members of this group.');
            }

            $existingInvitation = \App\Models\GroupInvitation::where('group_id', $group->id)
                ->where('student_id', $studentId)
                ->where('status', 'pending')
                ->first();

            if ($existingInvitation) {
                return back()->with('error', 'One or more selected students already have pending invitations.');
            }

            $invitedStudent = \App\Models\Student::where('student_id', $studentId)->first();
            if (! $invitedStudent) {
                return back()->with('error', 'One or more selected students were not found.');
            }

            if (! $invitedStudent->belongsToAcademicTerm($offeringTerm)) {
                $termLabel = $offeringTerm?->semester ?? 'this term';

                return back()->with('error', "Student {$invitedStudent->name} is not enrolled in the same semester as your group. Only students from {$termLabel} can be invited.");
            }

            $memberOffering = $invitedStudent->getCurrentOffering();
            if (! $memberOffering || $memberOffering->id !== $group->offering->id) {
                return back()->with('error', "Student {$invitedStudent->name} is not enrolled in the same capstone offering as your group. All group members must be enrolled in {$group->offering->offer_code} ({$group->offering->subject_code}).");
            }

            $existingGroup = \App\Models\Group::whereHas('members', function ($q) use ($invitedStudent) {
                $q->where('group_members.student_id', $invitedStudent->student_id);
            })->where('academic_term_id', $group->offering->academic_term_id)->first();

            if ($existingGroup) {
                return back()->with('error', "Student {$invitedStudent->name} is already a member of another group in {$group->offering->academicTerm->semester}.");
            }
        }

        foreach ($requestedStudentIds as $studentId) {
            \App\Models\GroupInvitation::create([
                'group_id' => $group->id,
                'student_id' => $studentId,
                'invited_by_student_id' => $student->student_id,
                'message' => $request->message,
                'status' => 'pending',
            ]);
        }

        $count = $requestedStudentIds->count();
        $message = $count === 1
            ? 'Invitation sent successfully! The student will be notified and can accept or decline the invitation.'
            : "{$count} invitations sent successfully! The students will be notified and can accept or decline the invitations.";

        return back()->with('success', $message);
    }

    public function acceptInvitation(Request $request, $invitationId)
    {
        $student = $this->getAuthenticatedStudent();

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }

        $invitation = \App\Models\GroupInvitation::where('id', $invitationId)
            ->where('student_id', $student->student_id)
            ->first();

        if (! $invitation) {
            return back()->with('error', 'Invitation not found.');
        }

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'Invitation has already been processed.');
        }

        $group = $invitation->group;

        if ($group->members()->count() >= 3) {
            $invitation->decline();

            return back()->with('error', 'Group is now full. Invitation declined.');
        }

        $existingGroup = Group::whereHas('members', function ($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first();

        if ($existingGroup && $existingGroup->id !== $group->id) {
            $invitation->decline();

            return back()->with('error', 'You are already a member of another group.');
        }

        if ($group->members()->where('group_members.student_id', $student->student_id)->exists()) {
            $invitation->accept();

            return back()->with('success', 'Invitation accepted! You are already a member of this group.');
        }

        try {

            $group->members()->attach($student->student_id, ['role' => 'member']);
            $invitation->accept();

            return back()->with('success', 'Invitation accepted! You are now a member of the group.');
        } catch (\Illuminate\Database\QueryException $e) {

            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {

                $invitation->accept();

                return back()->with('success', 'Invitation accepted! You are now a member of the group.');
            }

            throw $e;
        }
    }

    public function declineInvitation(Request $request, $invitationId)
    {
        $student = $this->getAuthenticatedStudent();

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }

        $invitation = \App\Models\GroupInvitation::where('id', $invitationId)
            ->where('student_id', $student->student_id)
            ->where('status', 'pending')
            ->first();

        if (! $invitation) {
            return back()->with('error', 'Invitation not found or already processed.');
        }

        $invitation->decline();

        return back()->with('success', 'Invitation declined.');
    }

    public function invitations()
    {
        $student = $this->getAuthenticatedStudent();

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }

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
        $student = $this->getAuthenticatedStudent();

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }

        $group = Group::whereHas('members', function ($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first();

        if (! $group) {
            return back()->with('error', 'Group not found.');
        }

        $isLeader = $group->members()
            ->where('group_members.student_id', $student->student_id)
            ->where('group_members.role', 'leader')
            ->exists();

        if (! $isLeader) {
            return back()->with('error', 'Only the group leader can remove members.');
        }

        $memberToRemove = $group->members()
            ->where('group_members.student_id', $memberId)
            ->first();

        if ($memberToRemove && $memberToRemove->pivot->role === 'leader') {
            return back()->with('error', 'The group leader cannot be removed.');
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
        $student = $this->getAuthenticatedStudent();
        $group = $student ? Group::whereHas('members', function ($q) use ($student) {
            $q->where('group_members.student_id', $student->student_id);
        })->first() : null;
        if (! $group) {
            return back()->with('error', 'Group not found.');
        }
        if (! $group->faculty_id) {
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
            \App\Services\NotificationService::createSimpleNotification(
                'New Defense Request',
                'Group '.$group->name.' has requested a '.$request->defense_type.' defense',
                'coordinator',
                route('coordinator.defense.index')
            );

            return back()->with('success', 'Defense request submitted successfully! Coordinator will review and schedule your defense.');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while submitting your defense request.');
        }
    }

    public function cancelInvitation(Request $request, $invitationId)
    {
        $student = $this->getAuthenticatedStudent();

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }

        $invitation = \App\Models\GroupInvitation::findOrFail($invitationId);

        if ($invitation->invited_by_student_id !== $student->student_id) {
            return back()->with('error', 'You can only cancel invitations that you sent.');
        }

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'You can only cancel pending invitations.');
        }

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled successfully.');
    }

    public function cancelAdviserInvitation(AdviserInvitation $invitation)
    {
        $student = $this->getAuthenticatedStudent();

        if (! $student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }

        $inGroup = Group::where('id', $invitation->group_id)
            ->whereHas('members', function ($q) use ($student) {
                $q->where('group_members.student_id', $student->student_id);
            })
            ->exists();

        if (! $inGroup) {
            return back()->with('error', 'You can only cancel invitations for your own group.');
        }

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'You can only cancel pending invitations.');
        }

        $invitation->delete();

        return back()->with('success', 'Adviser invitation cancelled successfully.');
    }
}
