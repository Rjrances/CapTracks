<?php

namespace App\Http\Controllers;

use App\Models\GroupMilestoneTask;
use App\Models\MilestoneTemplate;
use Illuminate\Support\Facades\Auth;

class StudentMilestoneChecklistController extends Controller
{
    public function checklist()
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.group')->with('error', 'You must be part of a group to view the checklist.');
        }

        $templates = MilestoneTemplate::with(['tasks' => function ($query) {
            $query->orderBy('order');
        }])->get();

        $groupTaskStatus = GroupMilestoneTask::whereHas('groupMilestone', function ($query) use ($group) {
            $query->where('group_id', $group->id);
        })->get()->keyBy('milestone_task_id');

        return view('student.milestones.checklist', compact('group', 'templates', 'groupTaskStatus'));
    }

    private function getAuthenticatedStudent()
    {
        if (!Auth::guard('student')->check()) {
            return null;
        }

        $studentAccount = Auth::guard('student')->user();

        return $studentAccount->student;
    }
}
