<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DefenseSchedule;
use App\Models\DefensePanel;
use App\Models\Group;
use App\Models\User;
use App\Models\AcademicTerm;
use App\Models\Offering;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DefenseScheduleController extends Controller
{
    public function index(Request $request)
    {
        // Get the logged-in coordinator's offerings
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        
        $query = DefenseSchedule::with(['group', 'academicTerm', 'defensePanels.faculty'])
            ->whereHas('group', function($q) use ($coordinatorOfferings) {
                $q->whereIn('offering_id', $coordinatorOfferings);
            })
            ->orderBy('start_at', 'asc');

        // Filter by academic term
        if ($request->filled('academic_term_id')) {
            $query->where('academic_term_id', $request->academic_term_id);
        }

        // Filter by offering (only show coordinator's offerings)
        if ($request->filled('offering')) {
            $query->whereHas('group.offering', function ($q) use ($request, $coordinatorOfferings) {
                $q->where('id', $request->offering)->whereIn('id', $coordinatorOfferings);
            });
        }

        $defenseSchedules = $query->paginate(15);
        $academicTerms = AcademicTerm::orderBy('school_year', 'desc')->orderBy('semester', 'desc')->get();
        $offerings = Offering::whereIn('id', $coordinatorOfferings)->orderBy('subject_title')->get();

        return view('coordinator.defense.index', compact('defenseSchedules', 'academicTerms', 'offerings'));
    }

    public function create()
    {
        // Get the logged-in coordinator's offerings
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        
        // Only show groups from the coordinator's offerings
        $groups = Group::with(['members', 'adviser', 'offering'])
            ->whereIn('offering_id', $coordinatorOfferings)
            ->get();
            
        $faculty = User::whereHas('roles', function ($query) {
            $query->whereIn('role', ['teacher', 'coordinator']);
        })->get();
        $academicTerms = AcademicTerm::orderBy('school_year', 'desc')->orderBy('semester', 'desc')->get();

        return view('coordinator.defense.create', compact('groups', 'faculty', 'academicTerms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'stage' => 'required|in:proposal,60,100',
            'academic_term_id' => 'required|exists:academic_terms,id',
            'room' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'panel_members' => 'required|array|min:1',
            'panel_members.*.faculty_id' => 'required|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member,adviser'
        ]);

        // Check if the coordinator can create a schedule for this group
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $group = Group::findOrFail($validated['group_id']);
        if (!in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only create defense schedules for groups in your offerings.');
        }

        // Combine date and time into datetime
        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);

        // Check for double booking
        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room);
        if ($conflict) {
            return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Create defense schedule
            $schedule = DefenseSchedule::create([
                'group_id' => $validated['group_id'],
                'stage' => $validated['stage'],
                'academic_term_id' => $validated['academic_term_id'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'room' => $validated['room'],
                'status' => 'scheduled'
            ]);

            // Create panel members
            foreach ($validated['panel_members'] as $member) {
                DefensePanel::create([
                    'defense_schedule_id' => $schedule->id,
                    'faculty_id' => $member['faculty_id'],
                    'role' => $member['role']
                ]);
            }
            
            // Automatically add the group's adviser if assigned
            if ($schedule->group->adviser_id) {
                DefensePanel::create([
                    'defense_schedule_id' => $schedule->id,
                    'faculty_id' => $schedule->group->adviser_id,
                    'role' => 'adviser'
                ]);
            }
            
            // Automatically add the offering coordinator if the group has an offering
            if ($schedule->group->offering && $schedule->group->offering->teacher_id) {
                DefensePanel::create([
                    'defense_schedule_id' => $schedule->id,
                    'faculty_id' => $schedule->group->offering->teacher_id,
                    'role' => 'coordinator'
                ]);
            }

            DB::commit();
            return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create defense schedule.'])->withInput();
        }
    }

    public function show($id)
    {
        $defenseSchedule = DefenseSchedule::with(['group.members', 'group.adviser', 'group.offering', 'academicTerm', 'defensePanels.faculty'])->findOrFail($id);
        
        // Check if the coordinator can view this schedule (group must be in their offering)
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (!in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only view defense schedules for groups in your offerings.');
        }
        
        return view('coordinator.defense.show', compact('defenseSchedule'));
    }

    public function edit($id)
    {
        $defenseSchedule = DefenseSchedule::with(['group', 'academicTerm', 'defensePanels'])->findOrFail($id);
        
        // Check if the coordinator can edit this schedule (group must be in their offering)
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (!in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only edit defense schedules for groups in your offerings.');
        }
        
        // Only show groups from the coordinator's offerings
        $groups = Group::with(['members', 'adviser', 'offering'])
            ->whereIn('offering_id', $coordinatorOfferings)
            ->get();
        
        // Get faculty excluding coordinators and advisers of this specific group
        $faculty = User::whereHas('roles', function ($query) {
            $query->whereIn('role', ['teacher', 'coordinator']);
        })->where(function ($query) use ($defenseSchedule) {
            // Exclude the adviser of this group
            if ($defenseSchedule->group->adviser_id) {
                $query->where('id', '!=', $defenseSchedule->group->adviser_id);
            }
            
            // Exclude coordinators of this group's offering
            if ($defenseSchedule->group->offering_id) {
                $query->whereDoesntHave('offerings', function ($q) use ($defenseSchedule) {
                    $q->where('id', $defenseSchedule->group->offering_id);
                });
            }
        })->get();
        
        $academicTerms = AcademicTerm::orderBy('school_year', 'desc')->orderBy('semester', 'desc')->get();

        return view('coordinator.defense.edit', compact('defenseSchedule', 'groups', 'faculty', 'academicTerms'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'stage' => 'required|in:proposal,60,100',
            'academic_term_id' => 'required|exists:academic_terms,id',
            'room' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'panel_members' => 'required|array|min:1',
            'panel_members.*.faculty_id' => 'required|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member,adviser'
        ]);

        $schedule = DefenseSchedule::findOrFail($id);
        
        // Check if the coordinator can edit this schedule (group must be in their offering)
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $group = Group::findOrFail($validated['group_id']);
        if (!in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only edit defense schedules for groups in your offerings.');
        }

        // Combine date and time into datetime
        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);

        // Check for double booking (exclude current schedule)
        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room, $id);
        if ($conflict) {
            return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Update defense schedule
            $schedule->update([
                'group_id' => $validated['group_id'],
                'stage' => $validated['stage'],
                'academic_term_id' => $validated['academic_term_id'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'room' => $validated['room']
            ]);

            // Delete existing panel members
            DefensePanel::where('defense_schedule_id', $schedule->id)->delete();

            // Create new panel members
            foreach ($validated['panel_members'] as $member) {
                DefensePanel::create([
                    'defense_schedule_id' => $schedule->id,
                    'faculty_id' => $member['faculty_id'],
                    'role' => $member['role']
                ]);
            }
            
            // Automatically add the group's adviser if assigned
            if ($schedule->group->adviser_id) {
                DefensePanel::create([
                    'defense_schedule_id' => $schedule->id,
                    'faculty_id' => $schedule->group->adviser_id,
                    'role' => 'adviser'
                ]);
            }
            
            // Automatically add the offering coordinator if the group has an offering
            if ($schedule->group->offering && $schedule->group->offering->teacher_id) {
                DefensePanel::create([
                    'defense_schedule_id' => $schedule->id,
                    'faculty_id' => $schedule->group->offering->teacher_id,
                    'role' => 'coordinator'
                ]);
            }

            DB::commit();
            return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to update defense schedule.'])->withInput();
        }
    }

    public function destroy($id)
    {
        $defenseSchedule = DefenseSchedule::with('group')->findOrFail($id);
        
        // Check if the coordinator can delete this schedule (group must be in their offering)
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (!in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only delete defense schedules for groups in your offerings.');
        }
        
        try {
            DB::beginTransaction();

            // Delete defense panels first
            DefensePanel::where('defense_schedule_id', $id)->delete();

            // Delete defense schedule
            $defenseSchedule->delete();

            DB::commit();
            return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to delete defense schedule.'])->withInput();
        }
    }

    public function getAvailableFaculty(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'room' => 'required|string',
            'group_id' => 'required|exists:groups,id'
        ]);

        // Check if the coordinator can access faculty for this group
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $group = Group::with(['adviser', 'offering'])->find($request->group_id);
        if (!in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only access faculty for groups in your offerings.');
        }

        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);

        // Check for room conflicts
        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room);
        
        // Get available faculty excluding coordinators and advisers of this group
        $availableFaculty = User::whereHas('roles', function ($query) {
            $query->whereIn('role', ['teacher', 'coordinator']);
        })->where(function ($query) use ($group) {
            // Exclude the adviser of this group
            if ($group->adviser_id) {
                $query->where('id', '!=', $group->adviser_id);
            }
            
            // Exclude coordinators of this group's offering
            if ($group->offering_id) {
                $query->whereDoesntHave('offerings', function ($q) use ($group) {
                    $q->where('id', $group->offering_id);
                });
            }
        })->get();

        return response()->json([
            'availableFaculty' => $availableFaculty,
            'conflict' => $conflict,
            'message' => $conflict ? 'This room is already booked for the selected time slot.' : null
        ]);
    }

    private function checkDoubleBooking($startAt, $endAt, $room, $excludeId = null)
    {
        $query = DefenseSchedule::where('room', $room)
            ->where(function ($q) use ($startAt, $endAt) {
                $q->whereBetween('start_at', [$startAt, $endAt])
                  ->orWhereBetween('end_at', [$startAt, $endAt])
                  ->orWhere(function ($q2) use ($startAt, $endAt) {
                      $q2->where('start_at', '<=', $startAt)
                         ->where('end_at', '>=', $endAt);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
