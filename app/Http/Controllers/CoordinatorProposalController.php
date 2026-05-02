<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Offering;
use App\Models\ProjectSubmission;
use App\Models\SubmissionComment;
use App\Services\ActivityLogService;
use App\Services\DocumentPreviewService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CoordinatorProposalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        //offerings by coordinator-user
        $coordinatedOfferings = Offering::where('faculty_id', $user->faculty_id)
            ->where(function($query) {
                $query->whereIn('subject_title', ['Capstone Project I', 'Capstone Project II'])
                      ->orWhereIn('subject_code', ['CS-CAP-401', 'CS-CAP-402']);
            })
            ->with(['academicTerm', 'groups.members'])
            ->get();
        
        $proposalsByOffering = [];
        
        foreach ($coordinatedOfferings as $offering) {
            //all groups
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
        
        //proposal checker
        $studentGroup = $student->groups()->first();
        if (!$studentGroup) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Student is not in any group.');
        }
        
        $offering = $studentGroup->offering;
        if (!$offering || $offering->faculty_id !== $user->faculty_id) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'You can only review proposals from your coordinated offerings.');
        }

        $versionHistory = ProjectSubmission::where('student_id', $proposal->student_id)
            ->where('type', 'proposal')
            ->orderByDesc('version')
            ->orderByDesc('submitted_at')
            ->get();

        $comments = $proposal->comments()
            ->whereNull('parent_id')
            ->with(['user', 'children.user'])
            ->latest()
            ->get();

        return view('coordinator.proposals.show', compact('proposal', 'studentGroup', 'offering', 'versionHistory', 'comments'));
    }

    public function preview($id)
    {
        $user = Auth::user();
        $proposal = ProjectSubmission::findOrFail($id);

        if (!$this->coordinatorCanReviewProposal($proposal, $user)) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'You can only review proposals from your coordinated offerings.');
        }

        if ($proposal->type !== 'proposal') {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Invalid proposal.');
        }

        if (!$proposal->file_path || !Storage::disk('public')->exists($proposal->file_path)) {
            return redirect()->route('coordinator.proposals.show', $proposal->id)->with('error', 'The proposal file was not found.');
        }

        $student = $proposal->getStudentData();
        $studentGroup = $student ? $student->groups()->first() : null;

        return view('coordinator.proposals.preview', [
            'panel' => DocumentPreviewService::panelForSubmission($proposal),
            'studentGroup' => $studentGroup,
            'backUrl' => route('coordinator.proposals.show', $proposal->id),
        ]);
    }

    public function compareVersions($left, $right)
    {
        $user = Auth::user();
        $a = ProjectSubmission::findOrFail($left);
        $b = ProjectSubmission::findOrFail($right);

        if ((int) $a->id === (int) $b->id) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Choose two different versions.');
        }

        if ($a->student_id !== $b->student_id || $a->type !== 'proposal' || $b->type !== 'proposal') {
            return redirect()->route('coordinator.proposals.index')->with('error', 'Invalid version pair.');
        }

        if (!$this->coordinatorCanReviewProposal($a, $user)) {
            return redirect()->route('coordinator.proposals.index')->with('error', 'You can only review proposals from your coordinated offerings.');
        }

        foreach ([$a, $b] as $proposalRow) {
            if (!$proposalRow->file_path || !Storage::disk('public')->exists($proposalRow->file_path)) {
                return redirect()->route('coordinator.proposals.show', $a->id)->with('error', 'A version file was not found.');
            }
        }

        $student = $a->getStudentData();
        $studentGroup = $student ? $student->groups()->first() : null;

        return view('coordinator.proposals.compare', [
            'leftPanel' => DocumentPreviewService::panelForSubmission($a),
            'rightPanel' => DocumentPreviewService::panelForSubmission($b),
            'studentGroup' => $studentGroup,
            'backUrl' => route('coordinator.proposals.show', $a->id),
        ]);
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

    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:submission_comments,id',
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
            return redirect()->route('coordinator.proposals.index')->with('error', 'You can only comment on proposals from your coordinated offerings.');
        }

        if ($request->filled('parent_id')) {
            $parentComment = SubmissionComment::find($request->parent_id);
            if (!$parentComment || (int) $parentComment->project_submission_id !== (int) $proposal->id) {
                return back()->withErrors(['body' => 'Invalid parent comment.'])->withInput();
            }
        }

        SubmissionComment::create([
            'project_submission_id' => $proposal->id,
            'user_id' => $user->id,
            'student_id' => null,
            'body' => $request->body,
            'parent_id' => $request->parent_id,
        ]);

        ActivityLogService::logSubmissionCommentAdded($proposal, $user);

        return back()->with('success', 'Comment posted successfully.');
    }

    private function coordinatorCanReviewProposal(ProjectSubmission $proposal, $user): bool
    {
        $student = $proposal->getStudentData();
        if (!$student) {
            return false;
        }

        $studentGroup = $student->groups()->first();
        if (!$studentGroup) {
            return false;
        }

        $offering = $studentGroup->offering;

        return $offering && (int) $offering->faculty_id === (int) $user->faculty_id;
    }
}
