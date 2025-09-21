<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\DefenseSchedule;
use App\Models\StudentGroup;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class CalendarController extends Controller
{
    public function coordinatorCalendar()
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
        return view('calendar.coordinator', compact('defenses', 'calendarEvents'));
    }
    public function adviserCalendar()
    {
        $user = Auth::user();
        $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
            ->whereIn('status', ['scheduled'])
            ->whereHas('group', function($query) use ($user) {
                $query->where('adviser_id', $user->id);
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
    public function getCalendarData(Request $request)
    {
        $user = Auth::user();
        $defenses = collect();
        switch ($user->role) {
            case 'coordinator':
            case 'chairperson':
                $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
                    ->whereIn('status', ['scheduled'])
                    ->get();
                break;
            case 'adviser':
                $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
                    ->whereIn('status', ['scheduled'])
                    ->whereHas('group', function($query) use ($user) {
                        $query->where('adviser_id', $user->id);
                    })
                    ->get();
                break;
            case 'student':
                $group = Group::whereHas('members', function($query) use ($user) {
                    $query->where('group_members.student_id', $user->student_id);
                })->first();
                if ($group) {
                    $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
                        ->whereIn('status', ['scheduled'])
                        ->where('group_id', $group->id)
                        ->get();
                }
                break;
        }
        $events = $defenses->map(function ($defense) {
            $startDate = Carbon::parse($defense->start_at);
            $endDate = Carbon::parse($defense->end_at);
            return [
                'id' => $defense->id,
                'title' => $defense->group->name ?? 'Defense',
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'url' => route('coordinator.defense.show', $defense->id),
                'backgroundColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
                'borderColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
                'textColor' => $defense->status === 'scheduled' ? '#000000' : '#ffffff',
                'extendedProps' => [
                    'group' => $defense->group->name ?? 'N/A',
                    'defenseType' => $defense->stage_label ?? 'Defense',
                    'adviser' => $defense->group->adviser->name ?? 'N/A',
                    'status' => $defense->status,
                    'room' => $defense->room ?? 'TBD'
                ]
            ];
        });
        return response()->json($events);
    }
}
