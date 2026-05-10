<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\DefensePanel;
use App\Models\DefenseSchedule;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function coordinatorCalendar()
    {
        $user = Auth::user();
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $coordinatorOfferings = $user->offerings()
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id')
            ->toArray();

        $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'group.offering.teacher', 'panelists.faculty'])
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->whereHas('group', function ($query) use ($coordinatorOfferings) {
                $query->whereIn('offering_id', $coordinatorOfferings);
            })
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->orderBy('start_at')
            ->get();

        $myGroupIds = Group::whereHas('offering', function ($query) use ($user) {
            $query->where('faculty_id', $user->faculty_id);
        })->pluck('id')->toArray();
        $calendarEvents = $defenses->map(function ($defense) use ($myGroupIds) {
            $startDate = \Carbon\Carbon::parse($defense->start_at);
            $endDate = \Carbon\Carbon::parse($defense->end_at);
            $invitedPanels = $defense->panelists->whereIn('role', ['chair', 'member']);
            $hasConfirmedChair = $invitedPanels->where('role', 'chair')->where('status', 'accepted')->isNotEmpty();
            $hasConfirmedMember = $invitedPanels->where('role', 'member')->where('status', 'accepted')->isNotEmpty();
            $hasDeclinedInvite = $invitedPanels->where('status', 'declined')->isNotEmpty();

            $panelState = $hasDeclinedInvite
                ? 'replacement_needed'
                : (($hasConfirmedChair && $hasConfirmedMember) ? 'confirmed' : 'awaiting_confirmation');

            $displayStatus = match ($defense->status) {
                'completed' => 'Completed',
                'in_progress' => 'In progress',
                default => match ($panelState) {
                    'replacement_needed' => 'Replacement needed',
                    'awaiting_confirmation' => 'Awaiting panel confirmation',
                    default => 'Scheduled',
                },
            };

            $displayStatusVariant = match ($defense->status) {
                'completed' => 'success',
                'in_progress' => 'warning',
                default => match ($panelState) {
                    'replacement_needed' => 'danger',
                    'awaiting_confirmation' => 'warning text-dark',
                    default => 'primary',
                },
            };

            $eventClass = match ($defense->status) {
                'completed' => 'approved',
                'in_progress' => 'scheduled',
                default => match ($panelState) {
                    'replacement_needed' => 'declined',
                    'awaiting_confirmation' => 'pending',
                    default => 'scheduled',
                },
            };

            return [
                'id' => $defense->id,
                'title' => $defense->group->name ?? 'Defense',
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'className' => $eventClass,
                'backgroundColor' => in_array($defense->group_id, $myGroupIds) ? '#28a745' : '#6c757d',
                'borderColor' => in_array($defense->group_id, $myGroupIds) ? '#28a745' : '#6c757d',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'group' => $defense->group->name ?? 'N/A',
                    'defenseType' => $defense->stage_label ?? 'Defense',
                    'adviser' => $defense->group->adviser->name ?? 'N/A',
                    'coordinator' => $defense->group->offering->teacher->name ?? 'N/A',
                    'status' => $defense->status,
                    'panel_state' => $panelState,
                    'display_status' => $displayStatus,
                    'display_status_variant' => $displayStatusVariant,
                    'local_date' => $startDate->format('m/d/Y'),
                    'room' => $defense->room ?? 'TBD',
                    'time' => $startDate->format('g:i A'),
                    'students' => $defense->group->members->pluck('name')->join(', '),
                    'is_mine' => in_array($defense->group_id, $myGroupIds),
                    'panelists' => self::panelistsPayloadForCalendar($defense->panelists),
                ],
            ];
        })->toArray();
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }

        return view('calendar.coordinator', compact('defenses', 'calendarEvents', 'myGroupIds'));
    }

    /**
     * Chair and member panelists for coordinator calendar modal (ordered: chair, then member).
     *
     * @param  \Illuminate\Support\Collection<int, DefensePanel>  $panels
     * @return list<array{role: string, role_label: string, name: string, status: string, status_label: string}>
     */
    private static function panelistsPayloadForCalendar($panels): array
    {
        return $panels
            ->whereIn('role', ['chair', 'member'])
            ->sortBy(fn (DefensePanel $p) => $p->role === 'chair' ? 0 : 1)
            ->values()
            ->map(fn (DefensePanel $p) => [
                'role' => $p->role,
                'role_label' => $p->role_label,
                'name' => $p->faculty?->name ?? 'Unknown',
                'status' => $p->status ?? 'pending',
                'status_label' => $p->status_label,
            ])
            ->all();
    }

    public function adviserCalendar()
    {
        $user = Auth::user();
        $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->whereHas('group', function ($query) use ($user) {
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
                    'students' => $defense->group->members->pluck('name')->join(', '),
                ],
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
        if (! $studentId) {
            return redirect('/login')->withErrors(['auth' => 'Student access required for this page.']);
        }
        $group = Group::whereHas('members', function ($query) use ($studentId) {
            $query->where('group_members.student_id', $studentId);
        })->first();
        $defenses = collect();
        $calendarEvents = collect();
        if ($group) {
            $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
                ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
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
                        'adviser' => $defense->group->adviser->name ?? 'N/A',
                    ],
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
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
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
                    'students' => $defense->group->members->pluck('name')->join(', '),
                ],
            ];
        })->toArray();
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }

        return view('calendar.chairperson', compact('defenses', 'calendarEvents'));
    }
}
