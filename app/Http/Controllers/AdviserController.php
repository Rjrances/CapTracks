<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdviserInvitation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdviserController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        // Get pending adviser invitations
        $pendingInvitations = AdviserInvitation::with(['group', 'group.members'])
            ->where('faculty_id', $user->id)
            ->pending()
            ->get();

        // Get groups where user is the adviser
        $adviserGroups = Group::with(['members', 'adviserInvitations'])
            ->where('adviser_id', $user->id)
            ->get();

        // Get recent notifications
        $notifications = \App\Models\Notification::where('role', 'adviser')
            ->latest()
            ->take(5)
            ->get();

        return view('adviser.dashboard', compact('pendingInvitations', 'adviserGroups', 'notifications'));
    }

    public function invitations()
    {
        $user = Auth::user();
        
        $invitations = AdviserInvitation::with(['group', 'group.members'])
            ->where('faculty_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('adviser.invitations', compact('invitations'));
    }

    public function respondToInvitation(Request $request, AdviserInvitation $invitation)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined',
            'response_message' => 'nullable|string|max:500',
        ]);

        // Check if user is the invited faculty
        if ($invitation->faculty_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Check if invitation is still pending
        if (!$invitation->isPending()) {
            return back()->with('error', 'This invitation has already been responded to.');
        }

        $invitation->update([
            'status' => $request->status,
            'response_message' => $request->response_message,
            'responded_at' => now(),
        ]);

        // If accepted, assign as adviser to the group
        if ($request->status === 'accepted') {
            $invitation->group->update(['adviser_id' => Auth::id()]);
            
            // Create notification for the group
            \App\Models\Notification::create([
                'title' => 'Adviser Invitation Accepted',
                'description' => 'Your adviser invitation has been accepted by ' . Auth::user()->name,
                'role' => 'student',
            ]);
        } else {
            // Create notification for the group
            \App\Models\Notification::create([
                'title' => 'Adviser Invitation Declined',
                'description' => 'Your adviser invitation has been declined by ' . Auth::user()->name,
                'role' => 'student',
            ]);
        }

        return back()->with('success', 'Invitation response submitted successfully.');
    }

    public function myGroups()
    {
        $user = Auth::user();
        
        $groups = Group::with(['members', 'adviserInvitations'])
            ->where('adviser_id', $user->id)
            ->paginate(10);

        return view('adviser.groups', compact('groups'));
    }

    public function groupDetails(Group $group)
    {
        // Check if user is the adviser of this group
        if ($group->adviser_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('adviser.group-details', compact('group'));
    }
} 