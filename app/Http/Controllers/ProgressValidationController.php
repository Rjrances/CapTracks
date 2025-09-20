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
    public function dashboard(Request $request)
    {
        $filters = $request->only([
            'academic_term_id', 'faculty_id', 'search'
        ]);
        $readyGroups = $this->progressValidationService->getGroupsReadyFor60PercentDefense();
        $needingAttentionGroups = $this->progressValidationService->getGroupsNeedingAttentionFor60PercentDefense();
        $allGroups = $this->progressValidationService->getFilteredGroupsForProgressValidation($filters);
        $stats = [
            'total_groups' => $allGroups->count(),
            'ready_for_60_percent' => $readyGroups->count(),
            'needing_attention' => $needingAttentionGroups->count(),
            'no_adviser' => $allGroups->whereNull('faculty_id')->count(),
            'below_40_percent' => $allGroups->filter(fn($g) => $g->overall_progress_percentage < 40)->count()
        ];
        $filterOptions = $this->progressValidationService->getFilterOptions();
        return view('coordinator.progress-validation.dashboard', compact(
            'readyGroups', 
            'needingAttentionGroups', 
            'allGroups', 
            'stats',
            'filters',
            'filterOptions'
        ));
    }
    public function groupReadinessReport(Group $group)
    {
        $report = $this->progressValidationService->get60PercentDefenseReadinessReport($group);
        return view('coordinator.progress-validation.group-report', compact('group', 'report'));
    }
    public function allGroupsStatus(Request $request)
    {
        $filters = $request->only([
            'academic_term_id', 'faculty_id', 'search'
        ]);
        $groups = $this->progressValidationService->getFilteredGroupsForProgressValidation($filters)
            ->map(function ($group) {
                $report = $this->progressValidationService->get60PercentDefenseReadinessReport($group);
                return [
                    'group' => $group,
                    'report' => $report
                ];
            });
        $filterOptions = $this->progressValidationService->getFilterOptions();
        return view('coordinator.progress-validation.all-groups', compact('groups', 'filters', 'filterOptions'));
    }
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
    private function exportToExcel($reports)
    {
        return response()->json(['message' => 'Excel export not implemented yet']);
    }
    private function exportToPdf($reports)
    {
        return response()->json(['message' => 'PDF export not implemented yet']);
    }
    public function adviserGroupReadiness(Group $group)
    {
        if (auth()->user()->faculty_id !== $group->faculty_id) {
            abort(403, 'You are not authorized to view this group\'s readiness report.');
        }
        $report = $this->progressValidationService->get60PercentDefenseReadinessReport($group);
        return view('adviser.progress-validation.group-readiness', compact('group', 'report'));
    }
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
