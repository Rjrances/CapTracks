<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\ProjectSubmission;
use App\Models\SubmissionComment;
use App\Services\ActivityLogService;
use App\Services\DocumentPreviewService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdviserProposalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedGroupId = $request->query('group');
        $groups = Group::where('faculty_id', $user->faculty_id)
            ->with(['members', 'members.submissions' => function($query) {
                $query->where('type', 'proposal')->latest();
            }]);

        if ($selectedGroupId) {
            $groups->where('id', (int) $selectedGroupId);
        }

        $groups = $groups
            ->get();
        $proposalsByGroup = [];
        
        $allProposals = collect();
        
        foreach ($groups as $group) {
            $proposals = $group->members->flatMap->submissions
                ->where('type', 'proposal')
                ->sortByDesc('submitted_at');
            
            $allProposals = $allProposals->merge($proposals);
            
            if ($proposals->isNotEmpty()) {
                $proposalsByGroup[$group->id] = [
                    'group' => $group,
                    'proposals' => $proposals,
                    'pending_count' => $proposals->where('status', 'pending')->count(),
                    'approved_count' => $proposals->where('status', 'approved')->count(),
                    'rejected_count' => $proposals->where('status', 'rejected')->count(),
                ];
            }
        }
        
        $stats = [
            'total_proposals' => $allProposals->count(),
            'pending_review' => $allProposals->where('status', 'pending')->count(),
            'approved' => $allProposals->where('status', 'approved')->count(),
            'rejected' => $allProposals->where('status', 'rejected')->count(),
        ];
        
        return view('adviser.proposal.index', compact('proposalsByGroup', 'stats', 'selectedGroupId'));
    }
    public function show($id)
    {
        $user = Auth::user();
        $proposal = ProjectSubmission::findOrFail($id);
        $student = $proposal->getStudentData();
        $studentGroup = $student ? $student->groups()->first() : null;
        if (!$studentGroup || $studentGroup->faculty_id !== $user->faculty_id) {
            return redirect()->route('adviser.proposal.index')->with('error', 'You can only review proposals from your assigned groups.');
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

        return view('adviser.proposal.show', compact('proposal', 'studentGroup', 'versionHistory', 'comments'));
    }

    public function preview($id)
    {
        $user = Auth::user();
        $proposal = ProjectSubmission::findOrFail($id);
        $student = $proposal->getStudentData();
        $studentGroup = $student ? $student->groups()->first() : null;
        if (!$studentGroup || $studentGroup->faculty_id !== $user->faculty_id) {
            return redirect()->route('adviser.proposal.index')->with('error', 'You can only review proposals from your assigned groups.');
        }
        if ($proposal->type !== 'proposal') {
            return redirect()->route('adviser.proposal.index')->with('error', 'Invalid proposal.');
        }
        if (!$proposal->file_path || !Storage::disk('public')->exists($proposal->file_path)) {
            return redirect()->route('adviser.proposal.show', $proposal->id)->with('error', 'The proposal file was not found.');
        }

        return view('adviser.proposal.preview', [
            'panel' => DocumentPreviewService::panelForSubmission($proposal),
            'studentGroup' => $studentGroup,
            'backUrl' => route('adviser.proposal.show', $proposal->id),
        ]);
    }

    public function compareVersions($left, $right)
    {
        $user = Auth::user();
        $a = ProjectSubmission::findOrFail($left);
        $b = ProjectSubmission::findOrFail($right);
        if ((int) $a->id === (int) $b->id) {
            return redirect()->route('adviser.proposal.index')->with('error', 'Choose two different versions.');
        }
        if ($a->student_id !== $b->student_id || $a->type !== 'proposal' || $b->type !== 'proposal') {
            return redirect()->route('adviser.proposal.index')->with('error', 'Invalid version pair.');
        }
        $student = $a->getStudentData();
        $studentGroup = $student ? $student->groups()->first() : null;
        if (!$studentGroup || $studentGroup->faculty_id !== $user->faculty_id) {
            return redirect()->route('adviser.proposal.index')->with('error', 'You can only review proposals from your assigned groups.');
        }
        foreach ([$a, $b] as $proposalRow) {
            if (!$proposalRow->file_path || !Storage::disk('public')->exists($proposalRow->file_path)) {
                return redirect()->route('adviser.proposal.show', $a->id)->with('error', 'A version file was not found.');
            }
        }

        return view('adviser.proposal.compare', [
            'leftPanel' => DocumentPreviewService::panelForSubmission($a),
            'rightPanel' => DocumentPreviewService::panelForSubmission($b),
            'studentGroup' => $studentGroup,
            'backUrl' => route('adviser.proposal.show', $a->id),
        ]);
    }

    public function edit($id)
    {
        $user = Auth::user();
        $proposal = ProjectSubmission::findOrFail($id);
        $student = $proposal->getStudentData();
        $studentGroup = $student ? $student->groups()->first() : null;
        if (!$studentGroup || $studentGroup->faculty_id !== $user->faculty_id) {
            return redirect()->route('adviser.proposal.index')->with('error', 'You can only review proposals from your assigned groups.');
        }
        return view('adviser.proposal.edit', compact('proposal', 'studentGroup'));
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
        $studentGroup = $student ? $student->groups()->first() : null;
        if (!$studentGroup || $studentGroup->faculty_id !== $user->faculty_id) {
            return redirect()->route('adviser.proposal.index')->with('error', 'You can only review proposals from your assigned groups.');
        }
        $proposal->update([
            'status' => $request->status,
            'teacher_comment' => $request->teacher_comment,
        ]);
        if ($request->status === 'approved') {
            NotificationService::proposalApproved(
                $proposal->getStudentData(),
                $studentGroup->name,
                $proposal->title ?? 'Project Proposal'
            );
        } else {
            NotificationService::proposalRejected(
                $proposal->getStudentData(),
                $studentGroup->name,
                $proposal->title ?? 'Project Proposal',
                $request->teacher_comment
            );
        }
        $statusMessage = $request->status === 'approved' ? 'approved' : 'rejected';
        return redirect()->route('adviser.proposal.index')->with('success', "Proposal {$statusMessage} successfully. Student has been notified.");
    }
    public function getStats()
    {
        $user = Auth::user();
        $stats = [
            'total_proposals' => 0,
            'pending_review' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];
        
        $groups = Group::where('faculty_id', $user->faculty_id)
            ->with(['members', 'members.submissions' => function($query) {
                $query->where('type', 'proposal');
            }])
            ->get();
        
        $allProposals = collect();
        foreach ($groups as $group) {
            $proposals = $group->members->flatMap->submissions->where('type', 'proposal');
            $allProposals = $allProposals->merge($proposals);
        }
        
        $stats['total_proposals'] = $allProposals->count();
        $stats['pending_review'] = $allProposals->where('status', 'pending')->count();
        $stats['approved'] = $allProposals->where('status', 'approved')->count();
        $stats['rejected'] = $allProposals->where('status', 'rejected')->count();
        
        return response()->json($stats);
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
        $studentGroup = $student ? $student->groups()->first() : null;
            if ($studentGroup && $studentGroup->faculty_id === $user->faculty_id) {
                $proposal->update([
                    'status' => $request->status,
                    'teacher_comment' => $request->teacher_comment,
                ]);
                if ($request->status === 'approved') {
                    NotificationService::proposalApproved(
                        $proposal->getStudentData(),
                        $studentGroup->name,
                        $proposal->title ?? 'Project Proposal'
                    );
                } else {
                    NotificationService::proposalRejected(
                        $proposal->getStudentData(),
                        $studentGroup->name,
                        $proposal->title ?? 'Project Proposal',
                        $request->teacher_comment
                    );
                }
                $updatedCount++;
            }
        }
        $statusMessage = $request->status === 'approved' ? 'approved' : 'rejected';
        return redirect()->route('adviser.proposal.index')->with('success', "{$updatedCount} proposals {$statusMessage} successfully.");
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
        $studentGroup = $student ? $student->groups()->first() : null;

        if (!$studentGroup || $studentGroup->faculty_id !== $user->faculty_id) {
            return redirect()->route('adviser.proposal.index')->with('error', 'You can only comment on proposals from your assigned groups.');
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
}
