<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ProjectSubmission;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
class AdviserProposalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $groups = Group::where('adviser_id', $user->id)
            ->with(['members', 'members.submissions' => function($query) {
                $query->where('type', 'proposal')->latest();
            }])
            ->get();
        $proposalsByGroup = [];
        foreach ($groups as $group) {
            $proposals = $group->members->flatMap->submissions
                ->where('type', 'proposal')
                ->sortByDesc('submitted_at');
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
        return view('adviser.proposal.index', compact('proposalsByGroup'));
    }
    public function show($id)
    {
        $user = Auth::user();
        $proposal = ProjectSubmission::with(['student', 'student.groups'])->findOrFail($id);
        $studentGroup = $proposal->student->groups()->first();
        if (!$studentGroup || $studentGroup->adviser_id !== $user->id) {
            return redirect()->route('adviser.proposal.index')->with('error', 'You can only review proposals from your assigned groups.');
        }
        return view('adviser.proposal.show', compact('proposal', 'studentGroup'));
    }
    public function edit($id)
    {
        $user = Auth::user();
        $proposal = ProjectSubmission::with(['student', 'student.groups'])->findOrFail($id);
        $studentGroup = $proposal->student->groups()->first();
        if (!$studentGroup || $studentGroup->adviser_id !== $user->id) {
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
        $proposal = ProjectSubmission::with(['student', 'student.groups'])->findOrFail($id);
        $studentGroup = $proposal->student->groups()->first();
        if (!$studentGroup || $studentGroup->adviser_id !== $user->id) {
            return redirect()->route('adviser.proposal.index')->with('error', 'You can only review proposals from your assigned groups.');
        }
        $proposal->update([
            'status' => $request->status,
            'teacher_comment' => $request->teacher_comment,
        ]);
        if ($request->status === 'approved') {
            NotificationService::proposalApproved(
                $proposal->student,
                $studentGroup->name,
                $proposal->title ?? 'Project Proposal'
            );
        } else {
            NotificationService::proposalRejected(
                $proposal->student,
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
        $groups = Group::where('adviser_id', $user->id)->pluck('id');
        if ($groups->isNotEmpty()) {
            $proposals = ProjectSubmission::where('type', 'proposal')
                ->whereHas('student.groups', function($query) use ($groups) {
                    $query->whereIn('group_id', $groups);
                })
                ->get();
            $stats['total_proposals'] = $proposals->count();
            $stats['pending_review'] = $proposals->where('status', 'pending')->count();
            $stats['approved'] = $proposals->where('status', 'approved')->count();
            $stats['rejected'] = $proposals->where('status', 'rejected')->count();
        }
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
            $proposal = ProjectSubmission::with(['student', 'student.groups'])->find($proposalId);
            $studentGroup = $proposal->student->groups()->first();
            if ($studentGroup && $studentGroup->adviser_id === $user->id) {
                $proposal->update([
                    'status' => $request->status,
                    'teacher_comment' => $request->teacher_comment,
                ]);
                if ($request->status === 'approved') {
                    NotificationService::proposalApproved(
                        $proposal->student,
                        $studentGroup->name,
                        $proposal->title ?? 'Project Proposal'
                    );
                } else {
                    NotificationService::proposalRejected(
                        $proposal->student,
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
}
