<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\ProgressValidationService;
use Illuminate\Http\Request;

class ProgressValidationController extends Controller
{
    protected $progressValidationService;

    public function __construct(ProgressValidationService $progressValidationService)
    {
        $this->progressValidationService = $progressValidationService;
    }

    /**
     * Show progress validation dashboard for coordinators
     */
    public function dashboard()
    {
        $readyGroups = $this->progressValidationService->getGroupsReadyFor60PercentDefense();
        $needingAttentionGroups = $this->progressValidationService->getGroupsNeedingAttentionFor60PercentDefense();
        
        $allGroups = Group::with(['adviser', 'members', 'groupMilestones.milestoneTemplate'])->get();
        
        $stats = [
            'total_groups' => $allGroups->count(),
            'ready_for_60_percent' => $readyGroups->count(),
            'needing_attention' => $needingAttentionGroups->count(),
            'no_adviser' => $allGroups->whereNull('adviser_id')->count(),
            'below_40_percent' => $allGroups->filter(fn($g) => $g->overall_progress_percentage < 40)->count()
        ];

        return view('coordinator.progress-validation.dashboard', compact(
            'readyGroups', 
            'needingAttentionGroups', 
            'allGroups', 
            'stats'
        ));
    }

    /**
     * Show detailed readiness report for a specific group
     */
    public function groupReadinessReport(Group $group)
    {
        $report = $this->progressValidationService->get60PercentDefenseReadinessReport($group);
        
        return view('coordinator.progress-validation.group-report', compact('group', 'report'));
    }

    /**
     * Show all groups with their readiness status
     */
    public function allGroupsStatus()
    {
        $groups = Group::with(['adviser', 'members', 'groupMilestones.milestoneTemplate'])
            ->orderBy('name')
            ->get()
            ->map(function ($group) {
                $report = $this->progressValidationService->get60PercentDefenseReadinessReport($group);
                return [
                    'group' => $group,
                    'report' => $report
                ];
            });

        return view('coordinator.progress-validation.all-groups', compact('groups'));
    }

    /**
     * API endpoint to get readiness status (for AJAX requests)
     */
    public function getReadinessStatus(Group $group)
    {
        $report = $this->progressValidationService->get60PercentDefenseReadinessReport($group);
        
        return response()->json([
            'group_id' => $group->id,
            'group_name' => $group->name,
            'is_ready' => $report['is_ready'],
            'overall_progress' => $report['overall_progress'],
            'issues' => $report['issues'],
            'warnings' => $report['warnings'],
            'recommendations' => $report['recommendations']
        ]);
    }

    /**
     * Export readiness report to PDF/Excel
     */
    public function exportReadinessReport(Request $request)
    {
        $format = $request->get('format', 'pdf');
        $groups = Group::with(['adviser', 'members', 'groupMilestones.milestoneTemplate'])->get();
        
        $reports = $groups->map(function ($group) {
            return [
                'group' => $group,
                'report' => $this->progressValidationService->get60PercentDefenseReadinessReport($group)
            ];
        });

        if ($format === 'excel') {
            return $this->exportToExcel($reports);
        }

        return $this->exportToPdf($reports);
    }

    /**
     * Export to Excel
     */
    private function exportToExcel($reports)
    {
        // TODO: Implement Excel export
        return response()->json(['message' => 'Excel export not implemented yet']);
    }

    /**
     * Export to PDF
     */
    private function exportToPdf($reports)
    {
        // TODO: Implement PDF export
        return response()->json(['message' => 'PDF export not implemented yet']);
    }

    /**
     * Show adviser view of group readiness
     */
    public function adviserGroupReadiness(Group $group)
    {
        // Check if current user is the group's adviser
        if (auth()->user()->id !== $group->adviser_id) {
            abort(403, 'You are not authorized to view this group\'s readiness report.');
        }

        $report = $this->progressValidationService->get60PercentDefenseReadinessReport($group);
        
        return view('adviser.progress-validation.group-readiness', compact('group', 'report'));
    }

    /**
     * Show student view of their group's readiness
     */
    public function studentGroupReadiness()
    {
        $student = auth()->user()->student;
        if (!$student) {
            abort(403, 'Student access required.');
        }

        $group = $student->groups->first();
        if (!$group) {
            return view('student.progress-validation.no-group');
        }

        $report = $this->progressValidationService->get60PercentDefenseReadinessReport($group);
        
        return view('student.progress-validation.group-readiness', compact('group', 'report'));
    }
}
