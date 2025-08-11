<?php

namespace App\Http\Controllers\Chairperson;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DefenseSchedule;
use App\Models\DefensePanel;
use App\Models\Group;
use App\Models\User;
use App\Models\AcademicTerm;
use Illuminate\Support\Facades\DB;

class DefenseScheduleController extends Controller
{
    /**
     * Get the current active academic term
     */
    private function getActiveTerm()
    {
        return AcademicTerm::where('is_active', true)->first();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        $showAllTerms = $request->get('show_all', false);
        
        $defenseSchedules = DefenseSchedule::with(['group.adviser', 'group.members', 'academicTerm', 'panelists'])
            ->when($activeTerm && !$showAllTerms, function($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->orderBy('start_at', 'desc')
            ->get();
        
        return view('chairperson.scheduling.index', compact('defenseSchedules', 'activeTerm', 'showAllTerms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $activeTerm = $this->getActiveTerm();
        
        $groups = Group::with(['adviser', 'members'])
            ->whereHas('adviser')
            ->when($activeTerm, function($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->get();
        
        $academicTerms = AcademicTerm::notArchived()->get();
        $faculty = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->get();
        
        return view('chairperson.scheduling.create', compact('groups', 'academicTerms', 'faculty', 'activeTerm'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'stage' => 'required|in:60,100',
            'academic_term_id' => 'required|exists:academic_terms,id',
            'start_at' => 'required|date|after:now',
            'end_at' => 'required|date|after:start_at',
            'room' => 'required|string|max:255',
            'remarks' => 'nullable|string',
            'panelists' => 'required|array|min:1',
            'panelists.*.faculty_id' => 'required|exists:users,id',
            'panelists.*.role' => 'required|in:chair,member,adviser',
        ]);

        // Check for conflicts
        $conflicts = DefenseSchedule::where('room', $request->room)
            ->where('status', 'scheduled')
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_at', [$request->start_at, $request->end_at])
                  ->orWhereBetween('end_at', [$request->start_at, $request->end_at])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_at', '<=', $request->start_at)
                         ->where('end_at', '>=', $request->end_at);
                  });
            })
            ->get();

        if ($conflicts->count() > 0) {
            return back()->withInput()->withErrors([
                'room' => 'This room is already booked for the selected time period. Conflicts: ' . 
                         $conflicts->pluck('formatted_time')->implode(', ')
            ]);
        }

        // Check for faculty conflicts
        $facultyConflicts = [];
        foreach ($request->panelists as $panelist) {
            $conflicts = DefenseSchedule::whereHas('panelists', function ($q) use ($panelist) {
                $q->where('faculty_id', $panelist['faculty_id']);
            })
            ->where('status', 'scheduled')
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_at', [$request->start_at, $request->end_at])
                  ->orWhereBetween('end_at', [$request->start_at, $request->end_at])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_at', '<=', $request->start_at)
                         ->where('end_at', '>=', $request->end_at);
                  });
            })
            ->get();

            if ($conflicts->count() > 0) {
                $faculty = User::find($panelist['faculty_id']);
                $facultyConflicts[] = $faculty->name . ' has conflicts: ' . 
                                    $conflicts->pluck('formatted_time')->implode(', ');
            }
        }

        if (!empty($facultyConflicts)) {
            return back()->withInput()->withErrors([
                'panelists' => 'Faculty conflicts detected: ' . implode('; ', $facultyConflicts)
            ]);
        }

        DB::transaction(function () use ($request) {
            $academicTermId = $request->academic_term_id;
            
            // If no academic term is selected, use the active term
            if (empty($academicTermId)) {
                $activeTerm = $this->getActiveTerm();
                if ($activeTerm) {
                    $academicTermId = $activeTerm->id;
                }
            }
            
            $defenseSchedule = DefenseSchedule::create([
                'group_id' => $request->group_id,
                'stage' => $request->stage,
                'academic_term_id' => $academicTermId,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
                'room' => $request->room,
                'remarks' => $request->remarks,
            ]);

            // Create panel assignments
            foreach ($request->panelists as $panelist) {
                DefensePanel::create([
                    'defense_schedule_id' => $defenseSchedule->id,
                    'faculty_id' => $panelist['faculty_id'],
                    'role' => $panelist['role'],
                ]);
            }
        });

        return redirect()->route('chairperson.scheduling.index')
            ->with('success', 'Defense schedule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DefenseSchedule $defenseSchedule)
    {
        $defenseSchedule->load(['group.adviser', 'group.members', 'academicTerm', 'panelists']);
        
        return view('chairperson.scheduling.show', compact('defenseSchedule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DefenseSchedule $defenseSchedule)
    {
        $defenseSchedule->load(['group.adviser', 'group.members', 'academicTerm', 'panelists']);
        
        $groups = Group::with(['adviser', 'members'])
            ->whereHas('adviser')
            ->get();
        
        $academicTerms = AcademicTerm::notArchived()->get();
        $faculty = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->get();
        
        return view('chairperson.scheduling.edit', compact('defenseSchedule', 'groups', 'academicTerms', 'faculty'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DefenseSchedule $defenseSchedule)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'stage' => 'required|in:60,100',
            'academic_term_id' => 'required|exists:academic_terms,id',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'room' => 'required|string|max:255',
            'remarks' => 'nullable|string',
            'panelists' => 'required|array|min:1',
            'panelists.*.faculty_id' => 'required|exists:users,id',
            'panelists.*.role' => 'required|in:chair,member,adviser',
        ]);

        // Check for conflicts (excluding current schedule)
        $conflicts = DefenseSchedule::where('room', $request->room)
            ->where('status', 'scheduled')
            ->where('id', '!=', $defenseSchedule->id)
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_at', [$request->start_at, $request->end_at])
                  ->orWhereBetween('end_at', [$request->start_at, $request->end_at])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_at', '<=', $request->start_at)
                         ->where('end_at', '>=', $request->end_at);
                  });
            })
            ->get();

        if ($conflicts->count() > 0) {
            return back()->withInput()->withErrors([
                'room' => 'This room is already booked for the selected time period. Conflicts: ' . 
                         $conflicts->pluck('formatted_time')->implode(', ')
            ]);
        }

        // Check for faculty conflicts (excluding current schedule)
        $facultyConflicts = [];
        foreach ($request->panelists as $panelist) {
            $conflicts = DefenseSchedule::whereHas('panelists', function ($q) use ($panelist) {
                $q->where('faculty_id', $panelist['faculty_id']);
            })
            ->where('status', 'scheduled')
            ->where('id', '!=', $defenseSchedule->id)
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_at', [$request->start_at, $request->end_at])
                  ->orWhereBetween('end_at', [$request->start_at, $request->end_at])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_at', '<=', $request->start_at)
                         ->where('end_at', '>=', $request->end_at);
                  });
            })
            ->get();

            if ($conflicts->count() > 0) {
                $faculty = User::find($panelist['faculty_id']);
                $facultyConflicts[] = $faculty->name . ' has conflicts: ' . 
                                    $conflicts->pluck('formatted_time')->implode(', ');
            }
        }

        if (!empty($facultyConflicts)) {
            return back()->withInput()->withErrors([
                'panelists' => 'Faculty conflicts detected: ' . implode('; ', $facultyConflicts)
            ]);
        }

        DB::transaction(function () use ($request, $defenseSchedule) {
            $defenseSchedule->update([
                'group_id' => $request->group_id,
                'stage' => $request->stage,
                'academic_term_id' => $request->academic_term_id,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
                'room' => $request->room,
                'remarks' => $request->remarks,
            ]);

            // Remove existing panel assignments
            $defenseSchedule->panels()->delete();

            // Create new panel assignments
            foreach ($request->panelists as $panelist) {
                DefensePanel::create([
                    'defense_schedule_id' => $defenseSchedule->id,
                    'faculty_id' => $panelist['faculty_id'],
                    'role' => $panelist['role'],
                ]);
            }
        });

        return redirect()->route('chairperson.scheduling.index')
            ->with('success', 'Defense schedule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DefenseSchedule $defenseSchedule)
    {
        $defenseSchedule->delete();

        return redirect()->route('chairperson.scheduling.index')
            ->with('success', 'Defense schedule deleted successfully.');
    }

    /**
     * Update the status of a defense schedule
     */
    public function updateStatus(Request $request, DefenseSchedule $defenseSchedule)
    {
        $request->validate([
            'status' => 'required|in:scheduled,completed,cancelled'
        ]);

        $defenseSchedule->update(['status' => $request->status]);

        return redirect()->route('chairperson.scheduling.index')
            ->with('success', 'Defense schedule status updated successfully.');
    }

    /**
     * Get available faculty for a specific time slot
     */
    public function getAvailableFaculty(Request $request)
    {
        $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'exclude_schedule_id' => 'nullable|exists:defense_schedules,id'
        ]);

        $conflictingFaculty = DefenseSchedule::whereHas('panelists')
            ->where('status', 'scheduled')
            ->where('id', '!=', $request->exclude_schedule_id)
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_at', [$request->start_at, $request->end_at])
                  ->orWhereBetween('end_at', [$request->start_at, $request->end_at])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_at', '<=', $request->start_at)
                         ->where('end_at', '>=', $request->end_at);
                  });
            })
            ->pluck('panelists.faculty_id')
            ->flatten()
            ->unique();

        $availableFaculty = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })
            ->whereNotIn('id', $conflictingFaculty)
            ->get();

        return response()->json($availableFaculty);
    }
}
