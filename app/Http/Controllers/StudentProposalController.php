<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectSubmission;
use App\Models\Group;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StudentProposalController extends Controller
{
    /**
     * Display the proposal submission form
     */
    public function index()
    {
        // Get student from either Auth or session
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = Student::find(session('student_id'));
        }

        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        // Get student's group
        $group = $student->groups()->first();
        
        if (!$group) {
            return redirect()->route('student.group')->with('error', 'You must be part of a group to submit a proposal.');
        }

        // Get existing proposal submission
        $existingProposal = ProjectSubmission::where('student_id', $student->id)
            ->where('type', 'proposal')
            ->latest()
            ->first();

        // Get proposal status and feedback
        $proposalStatus = $this->getProposalStatus($group, $existingProposal);

        return view('student.proposal.index', compact('group', 'existingProposal', 'proposalStatus'));
    }

    /**
     * Show the form for creating a new proposal
     */
    public function create()
    {
        // Get student from either Auth or session
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = Student::find(session('student_id'));
        }

        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        // Get student's group
        $group = $student->groups()->first();
        
        if (!$group) {
            return redirect()->route('student.group')->with('error', 'You must be part of a group to submit a proposal.');
        }

        // Check if proposal already exists
        $existingProposal = ProjectSubmission::where('student_id', $student->id)
            ->where('type', 'proposal')
            ->latest()
            ->first();

        if ($existingProposal && $existingProposal->status === 'approved') {
            return redirect()->route('student.proposal')->with('info', 'Your proposal has already been approved.');
        }

        return view('student.proposal.create', compact('group', 'existingProposal'));
    }

    /**
     * Store a newly created proposal
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'objectives' => 'required|string|min:100',
            'methodology' => 'required|string|min:100',
            'timeline' => 'required|string|min:50',
            'expected_outcomes' => 'required|string|min:50',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
        ]);

        // Get student from either Auth or session
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = Student::find(session('student_id'));
        }

        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        // Get student's group
        $group = $student->groups()->first();
        
        if (!$group) {
            return redirect()->route('student.group')->with('error', 'You must be part of a group to submit a proposal.');
        }

        // Store the file
        $path = $request->file('file')->store('proposals', 'public');

        // Create proposal submission
        $proposal = ProjectSubmission::create([
            'student_id' => $student->id,
            'file_path' => $path,
            'type' => 'proposal',
            'status' => 'pending',
            'submitted_at' => now(),
            'title' => $request->title,
            'objectives' => $request->objectives,
            'methodology' => $request->methodology,
            'timeline' => $request->timeline,
            'expected_outcomes' => $request->expected_outcomes,
        ]);

        return redirect()->route('student.proposal')->with('success', 'Proposal submitted successfully! It is now under review.');
    }

    /**
     * Display the specified proposal
     */
    public function show($id)
    {
        // Get student from either Auth or session
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = Student::find(session('student_id'));
        }

        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        $proposal = ProjectSubmission::with('student')->findOrFail($id);

        // Check if student owns this proposal
        if ($proposal->student_id !== $student->id) {
            return redirect()->route('student.proposal')->with('error', 'You can only view your own proposals.');
        }

        return view('student.proposal.show', compact('proposal'));
    }

    /**
     * Show the form for editing the specified proposal
     */
    public function edit($id)
    {
        // Get student from either Auth or session
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = Student::find(session('student_id'));
        }

        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        $proposal = ProjectSubmission::findOrFail($id);

        // Check if student owns this proposal
        if ($proposal->student_id !== $student->id) {
            return redirect()->route('student.proposal')->with('error', 'You can only edit your own proposals.');
        }

        // Check if proposal can be edited
        if ($proposal->status === 'approved') {
            return redirect()->route('student.proposal')->with('info', 'Approved proposals cannot be edited.');
        }

        return view('student.proposal.edit', compact('proposal'));
    }

    /**
     * Update the specified proposal
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'objectives' => 'required|string|min:100',
            'methodology' => 'required|string|min:100',
            'timeline' => 'required|string|min:50',
            'expected_outcomes' => 'required|string|min:50',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // 10MB max
        ]);

        // Get student from either Auth or session
        if (Auth::check()) {
            $student = Auth::user()->student;
        } else {
            $student = Student::find(session('student_id'));
        }

        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        $proposal = ProjectSubmission::findOrFail($id);

        // Check if student owns this proposal
        if ($proposal->student_id !== $student->id) {
            return redirect()->route('student.proposal')->with('error', 'You can only edit your own proposals.');
        }

        // Check if proposal can be edited
        if ($proposal->status === 'approved') {
            return redirect()->route('student.proposal')->with('info', 'Approved proposals cannot be edited.');
        }

        // Update proposal fields
        $proposal->update([
            'title' => $request->title,
            'objectives' => $request->objectives,
            'methodology' => $request->methodology,
            'timeline' => $request->timeline,
            'expected_outcomes' => $request->expected_outcomes,
        ]);

        // Update file if provided
        if ($request->hasFile('file')) {
            // Delete old file
            if ($proposal->file_path) {
                Storage::disk('public')->delete($proposal->file_path);
            }
            
            // Store new file
            $path = $request->file('file')->store('proposals', 'public');
            $proposal->update([
                'file_path' => $path,
                'status' => 'pending', // Reset status for review
                'submitted_at' => now(),
            ]);
        }

        return redirect()->route('student.proposal')->with('success', 'Proposal updated successfully! It is now under review again.');
    }

    /**
     * Get proposal status and readiness for defense
     */
    private function getProposalStatus($group, $existingProposal)
    {
        if (!$existingProposal) {
            return [
                'status' => 'not_submitted',
                'message' => 'No proposal submitted yet',
                'can_request_defense' => false,
                'next_step' => 'Submit your project proposal'
            ];
        }

        switch ($existingProposal->status) {
            case 'pending':
                return [
                    'status' => 'pending',
                    'message' => 'Proposal under review',
                    'can_request_defense' => false,
                    'next_step' => 'Wait for adviser feedback'
                ];
            
            case 'approved':
                return [
                    'status' => 'approved',
                    'message' => 'Proposal approved!',
                    'can_request_defense' => true,
                    'next_step' => 'Request proposal defense'
                ];
            
            case 'rejected':
                return [
                    'status' => 'rejected',
                    'message' => 'Proposal needs revision',
                    'can_request_defense' => false,
                    'next_step' => 'Review feedback and resubmit'
                ];
            
            default:
                return [
                    'status' => 'unknown',
                    'message' => 'Unknown status',
                    'can_request_defense' => false,
                    'next_step' => 'Contact your adviser'
                ];
        }
    }
}
