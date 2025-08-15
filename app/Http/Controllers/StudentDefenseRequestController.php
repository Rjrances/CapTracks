<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DefenseRequest;
use App\Models\Group;
use App\Models\MilestoneTemplate;
use Illuminate\Support\Facades\Auth;

class StudentDefenseRequestController extends Controller
{
    /**
     * Display a listing of the student's defense requests
     */
    public function index()
    {
        // Get the authenticated student
        $student = $this->getAuthenticatedStudent();
        
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        // Get student's group
        $group = $student->groups()->first();
        
        if (!$group) {
            return redirect()->route('student.group')->withErrors(['group' => 'You must be part of a group to request defenses.']);
        }

        // Get defense requests for this group
        $defenseRequests = DefenseRequest::where('group_id', $group->id)
            ->with(['group', 'defenseSchedule'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.defense-requests.index', compact('defenseRequests', 'group'));
    }

    /**
     * Show the form for creating a new defense request
     */
    public function create()
    {
        // Get the authenticated student
        $student = $this->getAuthenticatedStudent();
        
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        // Get student's group
        $group = $student->groups()->first();
        
        if (!$group) {
            return redirect()->route('student.group')->withErrors(['group' => 'You must be part of a group to request defenses.']);
        }

        // Check if group has an adviser
        if (!$group->adviser_id) {
            return redirect()->route('student.group')->withErrors(['adviser' => 'Your group must have an adviser before requesting a defense.']);
        }

        // Get available milestone templates for defense types
        $milestoneTemplates = MilestoneTemplate::where('status', 'active')->get();

        // Check if there are pending requests
        $pendingRequests = DefenseRequest::where('group_id', $group->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingRequests > 0) {
            return redirect()->route('student.defense-requests.index')
                ->withErrors(['pending' => 'You already have a pending defense request. Please wait for coordinator response.']);
        }

        return view('student.defense-requests.create', compact('group', 'milestoneTemplates'));
    }

    /**
     * Store a newly created defense request
     */
    public function store(Request $request)
    {
        // Get the authenticated student
        $student = $this->getAuthenticatedStudent();
        
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        // Get student's group
        $group = $student->groups()->first();
        
        if (!$group) {
            return redirect()->route('student.group')->withErrors(['group' => 'You must be part of a group to request defenses.']);
        }

        // Validate request
        $request->validate([
            'defense_type' => 'required|in:proposal,60_percent,100_percent',
            'student_message' => 'nullable|string|max:1000',
            'preferred_date' => 'required|date|after:today',
            'preferred_time' => 'required|date_format:H:i',
        ]);

        // Check if there are pending requests
        $pendingRequests = DefenseRequest::where('group_id', $group->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingRequests > 0) {
            return redirect()->route('student.defense-requests.index')
                ->withErrors(['pending' => 'You already have a pending defense request. Please wait for coordinator response.']);
        }

        try {
            // Create defense request
            DefenseRequest::create([
                'group_id' => $group->id,
                'defense_type' => $request->defense_type,
                'student_message' => $request->student_message,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            return redirect()->route('student.defense-requests.index')
                ->with('success', 'Defense request submitted successfully! The coordinator will review your request.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to submit defense request. Please try again.']);
        }
    }

    /**
     * Display the specified defense request
     */
    public function show(DefenseRequest $defenseRequest)
    {
        // Get the authenticated student
        $student = $this->getAuthenticatedStudent();
        
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        // Check if student belongs to the group
        $group = $student->groups()->first();
        if (!$group || $defenseRequest->group_id !== $group->id) {
            abort(403, 'Unauthorized access to this defense request.');
        }

        return view('student.defense-requests.show', compact('defenseRequest'));
    }

    /**
     * Cancel a pending defense request
     */
    public function cancel(DefenseRequest $defenseRequest)
    {
        // Get the authenticated student
        $student = $this->getAuthenticatedStudent();
        
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        // Check if student belongs to the group
        $group = $student->groups()->first();
        if (!$group || $defenseRequest->group_id !== $group->id) {
            abort(403, 'Unauthorized access to this defense request.');
        }

        // Check if request can be cancelled
        if (!$defenseRequest->isPending()) {
            return back()->withErrors(['status' => 'Only pending requests can be cancelled.']);
        }

        try {
            $defenseRequest->delete();
            return redirect()->route('student.defense-requests.index')
                ->with('success', 'Defense request cancelled successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to cancel defense request.']);
        }
    }

    /**
     * Get the authenticated student
     */
    private function getAuthenticatedStudent()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return $user->student;
        } elseif (session('is_student') && session('student_id')) {
            return \App\Models\Student::find(session('student_id'));
        }
        
        return null;
    }
}
