<?php

namespace App\Http\Controllers;

use App\Models\DefenseSchedule;
use App\Models\Group;
use App\Models\AcademicTerm;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function coordinatorCalendar()
    {
        $user = Auth::user();
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        // Show ALL scheduled defenses in the active term so coordinators
        // can see other groups' schedules and avoid room/time conflicts.
        $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'group.offering.teacher', 'panelists'])
            ->whereIn('status', ['scheduled'])
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->orderBy('start_at')
            ->get();

        // Only defenses for this coordinator's own groups can be edited.
        $myGroupIds = Group::whereHas('offering', function ($query) use ($user) {
            $query->where('faculty_id', $user->id);
        })->pluck('id')->toArray();
        $calendarEvents = $defenses->map(function ($defense) use ($myGroupIds) {
            $startDate = \Carbon\Carbon::parse($defense->start_at);
            $endDate   = \Carbon\Carbon::parse($defense->end_at);
            return [
                'id'              => $defense->id,
                'title'           => $defense->group->name ?? 'Defense',
                'start'           => $startDate->toISOString(),
                'end'             => $endDate->toISOString(),
                'backgroundColor' => in_array($defense->group_id, $myGroupIds) ? '#28a745' : '#6c757d',
                'borderColor'     => in_array($defense->group_id, $myGroupIds) ? '#28a745' : '#6c757d',
                'textColor'       => '#ffffff',
                'extendedProps'   => [
                    'group'       => $defense->group->name ?? 'N/A',
                    'defenseType' => $defense->stage_label ?? 'Defense',
                    'adviser'     => $defense->group->adviser->name ?? 'N/A',
                    'coordinator' => $defense->group->offering->teacher->name ?? 'N/A',
                    'status'      => $defense->status,
                    'room'        => $defense->room ?? 'TBD',
                    'time'        => $startDate->format('g:i A'),
                    'students'    => $defense->group->members->pluck('name')->join(', '),
                    'is_mine'     => in_array($defense->group_id, $myGroupIds),
                ],
            ];
        })->toArray();
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }
        return view('calendar.coordinator', compact('defenses', 'calendarEvents', 'myGroupIds'));
    }

    public function adviserCalendar()
    {
        $user = Auth::user();
        $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
            ->whereIn('status', ['scheduled'])
            ->whereHas('group', function($query) use ($user) {
                $query->where('faculty_id', $user->faculty_id);
            })
            ->orderBy('start_at')
            ->get();
        $calendarEvents = $defenses->map(function ($defense) {
            $startDate = \Carbon\Carbon::parse($defense->start_at);
            $endDate = \Carbon\Carbon::parse($defense->end_at);
            return [
                'id' => $defense->id,
                'title' => $defense->group->name ?? 'Defense',
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'backgroundColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
                'borderColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
                'textColor' => $defense->status === 'scheduled' ? '#000000' : '#ffffff',
                'extendedProps' => [
                    'group' => $defense->group->name ?? 'N/A',
                    'defenseType' => $defense->stage_label ?? 'Defense',
                    'groupId' => $defense->group_id,
                    'status' => $defense->status,
                    'room' => $defense->room ?? 'TBD',
                    'time' => $startDate->format('g:i A'),
                    'students' => $defense->group->members->pluck('name')->join(', ')
                ]
            ];
        })->toArray();
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }
        return view('calendar.adviser', compact('defenses', 'calendarEvents'));
    }

    public function studentCalendar()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            $student = $studentAccount->student;
            $studentId = $student ? $student->student_id : null;
        } else {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        if (!$studentId) {
            return redirect('/login')->withErrors(['auth' => 'Student access required for this page.']);
        }
        $group = Group::whereHas('members', function($query) use ($studentId) {
            $query->where('group_members.student_id', $studentId);
        })->first();
        $defenses = collect();
        $calendarEvents = collect();
        if ($group) {
            $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
                ->whereIn('status', ['scheduled'])
                ->where('group_id', $group->id)
                ->orderBy('start_at')
                ->get();
            $calendarEvents = $defenses->map(function ($defense) {
                $startDate = \Carbon\Carbon::parse($defense->start_at);
                $endDate = \Carbon\Carbon::parse($defense->end_at);
                return [
                    'id' => $defense->id,
                    'title' => $defense->group->name ?? 'Defense',
                    'start' => $startDate->toISOString(),
                    'end' => $endDate->toISOString(),
                    'backgroundColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
                    'borderColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
                    'textColor' => $defense->status === 'scheduled' ? '#000000' : '#ffffff',
                    'extendedProps' => [
                        'group' => $defense->group->name ?? 'N/A',
                        'defenseType' => $defense->stage_label ?? 'Defense',
                        'status' => $defense->status,
                        'room' => $defense->room ?? 'TBD',
                        'time' => $startDate->format('g:i A'),
                        'adviser' => $defense->group->adviser->name ?? 'N/A'
                    ]
                ];
            })->toArray();
        }
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }
        return view('calendar.student', compact('defenses', 'group', 'calendarEvents'));
    }

    public function chairpersonCalendar()
    {
        $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
            ->whereIn('status', ['scheduled'])
            ->orderBy('start_at')
            ->get();
        $calendarEvents = $defenses->map(function ($defense) {
            $startDate = \Carbon\Carbon::parse($defense->start_at);
            $endDate = \Carbon\Carbon::parse($defense->end_at);
            return [
                'id' => $defense->id,
                'title' => $defense->group->name ?? 'Defense',
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'backgroundColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
                'borderColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
                'textColor' => $defense->status === 'scheduled' ? '#000000' : '#ffffff',
                'extendedProps' => [
                    'group' => $defense->group->name ?? 'N/A',
                    'defenseType' => $defense->stage_label ?? 'Defense',
                    'adviser' => $defense->group->adviser->name ?? 'N/A',
                    'status' => $defense->status,
                    'room' => $defense->room ?? 'TBD',
                    'time' => $startDate->format('g:i A'),
                    'students' => $defense->group->members->pluck('name')->join(', ')
                ]
            ];
        })->toArray();
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }
        return view('calendar.chairperson', compact('defenses', 'calendarEvents'));
    }
}
