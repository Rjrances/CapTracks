<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectSubmission;
use App\Models\Group;
use App\Models\User;
use App\Models\Offering;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class CoordinatorProposalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get all Capstone offerings coordinated by this user
        $coordinatedOfferings = Offering::where('faculty_id', $user->faculty_id)
            ->where(function($query) {
                $query->whereIn('subject_title', ['Capstone Project I', 'Capstone Project II'])
                      ->orWhereIn('subject_code', ['CS-CAP-401', 'CS-CAP-402']);
            })
            ->with(['academicTerm', 'groups.members'])
            ->get();
        
        $proposalsByOffering = [];
        
        foreach ($coordinatedOfferings as $offering) {
            // Get all groups for this offering
            $groups = $offering->groups;
            
            $allProposals = collect();
            foreach ($groups as $group) {
                $groupProposals = ProjectSubmission::whereIn('student_id', $group->members->pluck('student_id'))
                    ->where('type', 'proposal')
                    ->get();
                
                $allProposals = $allProposals->merge($groupProposals);
            }
            
            if ($allProposals->isNotEmpty()) {
                $proposalsByOffering[$offering->id] = [
                    'offering' => $offering,
                    'proposals' => $allProposals->sortByDesc('submitted_at'),
                    'pending_count' => $allProposals->where('status', 'pending')->count(),
                    'approved_count' => $allProposals->where('status', 'approved')->count(),
                    'rejected_count' => $allProposals->where('status', 'rejected')->count(),
                    'total_groups' => $groups->count(),
                ];
            }
        }
        
        return view('coordinator.proposals.index', compact('proposalsByOffering'));
    }
    
    public function show($id)
    {
        $user = Auth::user();
        $proposal = ProjectSubmission::findOrFail($id);
        $student = $proposal->getStudentData();
        
        if (!$student) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Student not found.');
        }
        
        // Check if this proposal is from a group in one of the coordinator's offerings
        $studentGroup = $student->groups()->first();
        if (!$studentGroup) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Student is not in any group.');
        }
        
        $offering = $studentGroup->offering;
        if (!$offering || $offering->faculty_id !== $user->faculty_id) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'You can only review proposals from your coordinated offerings.');
        }
        
        return view('coordinator.proposals.show', compact('proposal', 'studentGroup', 'offering'));
    }
    
    public function edit($id)
    {
        $user = Auth::user();
        $proposal = ProjectSubmission::findOrFail($id);
        $student = $proposal->getStudentData();
        
        if (!$student) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Student not found.');
        }
        
        $studentGroup = $student->groups()->first();
        if (!$studentGroup) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Student is not in any group.');
        }
        
        $offering = $studentGroup->offering;
        if (!$offering || $offering->faculty_id !== $user->faculty_id) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'You can only review proposals from your coordinated offerings.');
        }
        
        return view('coordinator.proposals.edit', compact('proposal', 'studentGroup', 'offering'));
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'teacher_comment' => 'required|string|min:10',
        ]);
        
        $user = Auth::user();
        $proposal = ProjectSubmission::findOrFail($id);
        $student = $proposal->getStudentData();
        
        if (!$student) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Student not found.');
        }
        
        $studentGroup = $student->groups()->first();
        if (!$studentGroup) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Student is not in any group.');
        }
        
        $offering = $studentGroup->offering;
        if (!$offering || $offering->faculty_id !== $user->faculty_id) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'You can only review proposals from your coordinated offerings.');
        }
        
        $proposal->update([
            'status' => $request->status,
            'teacher_comment' => $request->teacher_comment,
        ]);
        
        if ($request->status === 'approved') {
            NotificationService::proposalApproved(
                $student,
                $studentGroup->name,
                $proposal->title ?? 'Project Proposal'
            );
        } else {
            NotificationService::proposalRejected(
                $student,
                $studentGroup->name,
                $proposal->title ?? 'Project Proposal',
                $request->teacher_comment
            );
        }
        
        $statusMessage = $request->status === 'approved' ? 'approved' : 'rejected';
        return redirect()->route('coordinator.proposals.index')->with('success', "Proposal {$statusMessage} successfully. Student has been notified.");
    }
    
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'proposal_ids' => 'required|array',
            'proposal_ids.*' => 'integer|exists:project_submissions,id',
            'status' => 'required|in:approved,rejected',
            'teacher_comment' => 'required|string|min:10',
        ]);
        
        $user = Auth::user();
        $updatedCount = 0;
        
        foreach ($request->proposal_ids as $proposalId) {
            $proposal = ProjectSubmission::find($proposalId);
            $student = $proposal->getStudentData();
            
            if (!$student) continue;
            
            $studentGroup = $student->groups()->first();
            if (!$studentGroup) continue;
            
            $offering = $studentGroup->offering;
            if (!$offering || $offering->faculty_id !== $user->faculty_id) continue;
            
            $proposal->update([
                'status' => $request->status,
                'teacher_comment' => $request->teacher_comment,
            ]);
            
            if ($request->status === 'approved') {
                NotificationService::proposalApproved(
                    $student,
                    $studentGroup->name,
                    $proposal->title ?? 'Project Proposal'
                );
            } else {
                NotificationService::proposalRejected(
                    $student,
                    $studentGroup->name,
                    $proposal->title ?? 'Project Proposal',
                    $request->teacher_comment
                );
            }
            
            $updatedCount++;
        }
        
        $statusMessage = $request->status === 'approved' ? 'approved' : 'rejected';
        return redirect()->route('coordinator.proposals.index')->with('success', "{$updatedCount} proposals {$statusMessage} successfully.");
    }
    
    public function getStats()
    {
        $user = Auth::user();
        
        $coordinatedOfferings = Offering::where('faculty_id', $user->faculty_id)
            ->where(function($query) {
                $query->whereIn('subject_title', ['Capstone Project I', 'Capstone Project II'])
                      ->orWhereIn('subject_code', ['CS-CAP-401', 'CS-CAP-402']);
            })
            ->get();
        $offeringIds = $coordinatedOfferings->pluck('id');
        
        $groups = Group::whereIn('offering_id', $offeringIds)->get();
        $groupIds = $groups->pluck('id');
        
        $memberIds = collect();
        foreach ($groups as $group) {
            $memberIds = $memberIds->merge($group->members->pluck('student_id'));
        }
        
        $proposals = ProjectSubmission::whereIn('student_id', $memberIds)
            ->where('type', 'proposal')
            ->get();
        
        return response()->json([
            'total_proposals' => $proposals->count(),
            'pending_proposals' => $proposals->where('status', 'pending')->count(),
            'approved_proposals' => $proposals->where('status', 'approved')->count(),
            'rejected_proposals' => $proposals->where('status', 'rejected')->count(),
            'total_offerings' => $coordinatedOfferings->count(),
            'total_groups' => $groups->count(),
        ]);
    }
}
