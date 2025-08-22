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
    /**
     * Show calendar for coordinators (all defenses)
     */
    public function coordinatorCalendar()
    {
        $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
            ->whereIn('status', ['scheduled'])
            ->orderBy('start_at')
            ->get();

        // Prepare calendar events data
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

        // Ensure calendarEvents is always an array
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }

        return view('calendar.coordinator', compact('defenses', 'calendarEvents'));
    }

    /**
     * Show calendar for advisers (their groups' defenses)
     */
    public function adviserCalendar()
    {
        $user = Auth::user();
        
        // Get defenses for groups where this user is the adviser
        $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
            ->whereIn('status', ['scheduled'])
            ->whereHas('group', function($query) use ($user) {
                $query->where('adviser_id', $user->id);
            })
            ->orderBy('start_at')
            ->get();

        // Prepare calendar events data
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

        // Ensure calendarEvents is always an array
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }

        return view('calendar.adviser', compact('defenses', 'calendarEvents'));
    }

    /**
     * Show calendar for students (their group's defenses)
     */
    public function studentCalendar()
    {
        // Check if user is authenticated via Laravel Auth (faculty/staff)
        if (Auth::check()) {
            $user = Auth::user();
            $studentId = $user->id;
        } elseif (session('is_student') && session('student_id')) {
            // Session-based student authentication
            $studentId = session('student_id');
        } else {
            // Not authenticated
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        
        // Get the student's group
        $group = Group::whereHas('members', function($query) use ($studentId) {
            $query->where('group_members.student_id', $studentId);
        })->first();

        $defenses = collect();
        $calendarEvents = collect();
        
        if ($group) {
            // Get defenses for this student's group
            $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
                ->whereIn('status', ['scheduled'])
                ->where('group_id', $group->id)
                ->orderBy('start_at')
                ->get();

            // Prepare calendar events data
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

        // Ensure calendarEvents is always an array
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }

        return view('calendar.student', compact('defenses', 'group', 'calendarEvents'));
    }

    /**
     * Show calendar for chairpersons (all defenses)
     */
    public function chairpersonCalendar()
    {
        $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
            ->whereIn('status', ['scheduled'])
            ->orderBy('start_at')
            ->get();

        // Prepare calendar events data
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

        // Ensure calendarEvents is always an array
        if (empty($calendarEvents)) {
            $calendarEvents = [];
        }

        return view('calendar.chairperson', compact('defenses', 'calendarEvents'));
    }

    /**
     * Get calendar data as JSON for AJAX requests
     */
    public function getCalendarData(Request $request)
    {
        $user = Auth::user();
        $defenses = collect();

        switch ($user->role) {
            case 'coordinator':
            case 'chairperson':
                // All defenses
                $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
                    ->whereIn('status', ['scheduled'])
                    ->get();
                break;
                
            case 'adviser':
                // Defenses for groups where user is adviser
                $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
                    ->whereIn('status', ['scheduled'])
                    ->whereHas('group', function($query) use ($user) {
                        $query->where('adviser_id', $user->id);
                    })
                    ->get();
                break;
                
            case 'student':
                // Defenses for student's group
                $group = Group::whereHas('members', function($query) use ($user) {
                    $query->where('group_members.student_id', $user->id);
                })->first();
                
                if ($group) {
                    $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
                        ->whereIn('status', ['scheduled'])
                        ->where('group_id', $group->id)
                        ->get();
                }
                break;
        }

        // Format for calendar
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
