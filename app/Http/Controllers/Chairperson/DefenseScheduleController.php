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
    private function getActiveTerm()
    {
        return AcademicTerm::where('is_active', true)->first();
    }
    public function index(Request $request)
    {
        $activeTerm = $this->getActiveTerm();
        $showAllTerms = $request->get('show_all', false);
        $defenseSchedules = DefenseSchedule::with(['group.members', 'group.adviser', 'defensePanels.faculty'])
            ->orderBy('start_at', 'desc')
            ->get();
        return view('chairperson.scheduling.index', compact('defenseSchedules', 'activeTerm', 'showAllTerms'));
    }
    public function create()
    {
        $activeTerm = $this->getActiveTerm();
        $groups = Group::with(['adviser', 'members'])
            ->whereHas('adviser')
            ->get();
        $defenseRequests = \App\Models\DefenseRequest::where('status', 'approved')->get();
        $faculty = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->get();
        return view('chairperson.scheduling.create', compact('groups', 'defenseRequests', 'faculty', 'activeTerm'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'defense_request_id' => 'required|exists:defense_requests,id',
            'defense_type' => 'required|in:proposal,60_percent,100_percent',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'required|date_format:H:i',
            'room' => 'required|string|max:255',
            'coordinator_notes' => 'nullable|string',
            'panelists' => 'required|array|min:1',
            'panelists.*.faculty_id' => 'required|exists:users,id',
            'panelists.*.role' => 'required|in:chair,member,adviser',
        ]);
        $conflicts = DefenseSchedule::where('room', $request->room)
            ->where('status', 'scheduled')
            ->where('scheduled_date', $request->scheduled_date)
            ->where('scheduled_time', $request->scheduled_time)
            ->get();
        if ($conflicts->count() > 0) {
            return back()->withInput()->withErrors([
                'room' => 'This room is already booked for the selected time period. Conflicts: ' . 
                         $conflicts->pluck('formatted_time')->implode(', ')
            ]);
        }
        $facultyConflicts = [];
        foreach ($request->panelists as $panelist) {
            $conflicts = DefenseSchedule::whereHas('panelists', function ($q) use ($panelist) {
                $q->where('faculty_id', $panelist['faculty_id']);
            })
            ->where('status', 'scheduled')
            ->where('scheduled_date', $request->scheduled_date)
            ->where('scheduled_time', $request->scheduled_time)
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
            $defenseSchedule = DefenseSchedule::create([
                'group_id' => $request->group_id,
                'defense_request_id' => $request->defense_request_id,
                'defense_type' => $request->defense_type,
                'scheduled_date' => $request->scheduled_date,
                'scheduled_time' => $request->scheduled_time,
                'room' => $request->room,
                'coordinator_notes' => $request->coordinator_notes,
            ]);
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
    public function show(DefenseSchedule $defenseSchedule)
    {
        $defenseSchedule->load(['group.adviser', 'group.members', 'defenseRequest', 'panelists']);
        return view('chairperson.scheduling.show', compact('defenseSchedule'));
    }
    public function edit(DefenseSchedule $defenseSchedule)
    {
        $defenseSchedule->load(['group.adviser', 'group.members', 'defenseRequest', 'panelists']);
        $groups = Group::with(['adviser', 'members'])
            ->whereHas('adviser')
            ->get();
        $defenseRequests = \App\Models\DefenseRequest::where('status', 'approved')->get();
        $faculty = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->get();
        return view('chairperson.scheduling.edit', compact('defenseSchedule', 'groups', 'defenseRequests', 'faculty'));
    }
    public function update(Request $request, DefenseSchedule $defenseSchedule)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
            'defense_request_id' => 'required|exists:defense_requests,id',
            'defense_type' => 'required|in:proposal,60_percent,100_percent',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'room' => 'required|string|max:255',
            'coordinator_notes' => 'nullable|string',
            'panelists' => 'required|array|min:1',
            'panelists.*.faculty_id' => 'required|exists:users,id',
            'panelists.*.role' => 'required|in:chair,member,adviser',
        ]);
        $conflicts = DefenseSchedule::where('room', $request->room)
            ->where('status', 'scheduled')
            ->where('id', '!=', $defenseSchedule->id)
            ->where('scheduled_date', $request->scheduled_date)
            ->where('scheduled_time', $request->scheduled_time)
            ->get();
        if ($conflicts->count() > 0) {
            return back()->withInput()->withErrors([
                'room' => 'This room is already booked for the selected time period. Conflicts: ' . 
                         $conflicts->pluck('formatted_time')->implode(', ')
            ]);
        }
        $facultyConflicts = [];
        foreach ($request->panelists as $panelist) {
            $conflicts = DefenseSchedule::whereHas('panelists', function ($q) use ($panelist) {
                $q->where('faculty_id', $panelist['faculty_id']);
            })
            ->where('status', 'scheduled')
            ->where('id', '!=', $defenseSchedule->id)
            ->where('scheduled_date', $request->scheduled_date)
            ->where('scheduled_time', $request->scheduled_time)
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
                'defense_request_id' => $request->defense_request_id,
                'defense_type' => $request->defense_type,
                'scheduled_date' => $request->scheduled_date,
                'scheduled_time' => $request->scheduled_time,
                'room' => $request->room,
                'coordinator_notes' => $request->coordinator_notes,
            ]);
            $defenseSchedule->panels()->delete();
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
    public function destroy(DefenseSchedule $defenseSchedule)
    {
        $defenseSchedule->delete();
        return redirect()->route('chairperson.scheduling.index')
            ->with('success', 'Defense schedule deleted successfully.');
    }
    public function updateStatus(Request $request, DefenseSchedule $defenseSchedule)
    {
        $request->validate([
            'status' => 'required|in:scheduled,completed,cancelled'
        ]);
        $defenseSchedule->update(['status' => $request->status]);
        return redirect()->route('chairperson.scheduling.index')
            ->with('success', 'Defense schedule status updated successfully.');
    }
    public function getAvailableFaculty(Request $request)
    {
        $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'exclude_schedule_id' => 'nullable|exists:defense_schedules,id'
        ]);
        $conflictingFaculty = DefenseSchedule::whereHas('panelists')
            ->where('status', 'scheduled')
            ->where('id', '!=', $request->exclude_schedule_id)
            ->where('scheduled_date', $request->scheduled_date)
            ->where('scheduled_time', $request->scheduled_time)
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
