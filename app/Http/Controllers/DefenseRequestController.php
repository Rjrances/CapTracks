<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\DefenseRequest;
use App\Models\DefenseSchedule;
use App\Models\DefensePanel;
use App\Models\Group;
use App\Models\User;
use App\Models\Notification;
class DefenseRequestController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'defense_type', 'search']);
        
        // Ensure all filter keys exist with default values
        $filters = array_merge([
            'status' => '',
            'defense_type' => '',
            'search' => ''
        ], $filters);
        
        $query = DefenseRequest::with(['group.members', 'group.adviser'])
            ->orderBy('created_at', 'desc');
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['defense_type'])) {
            $query->where('defense_type', $filters['defense_type']);
        }
        if (!empty($filters['search'])) {
            $query->whereHas('group', function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            });
        }
        $defenseRequests = $query->paginate(15);
        $filterOptions = [
            'statuses' => ['pending', 'approved', 'rejected', 'scheduled'],
            'defense_types' => ['proposal', '60_percent', '100_percent']
        ];
        return view('coordinator.defense.index', compact('defenseRequests', 'filters', 'filterOptions'));
    }
    public function createSchedule(DefenseRequest $defenseRequest)
    {
        if (!$defenseRequest->isPending() && !$defenseRequest->isApproved()) {
            return back()->with('error', 'This defense request cannot be scheduled.');
        }
        $availableFaculty = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->get();
        return view('coordinator.defense-requests.create-schedule', compact('defenseRequest', 'availableFaculty'));
    }
    public function storeSchedule(Request $request, DefenseRequest $defenseRequest)
    {
        $request->validate([
            'scheduled_date' => 'required|date|after:today',
            'scheduled_time' => 'required',
            'room' => 'required|string|max:255',
            'coordinator_notes' => 'nullable|string|max:1000',
            'adviser_id' => 'required|exists:users,id',
            'subject_coordinator_id' => 'required|exists:users,id',
            'panelist_1_id' => 'required|exists:users,id',
            'panelist_2_id' => 'required|exists:users,id',
        ]);
        try {
            $defenseSchedule = DefenseSchedule::create([
                'defense_request_id' => $defenseRequest->id,
                'group_id' => $defenseRequest->group_id,
                'defense_type' => $defenseRequest->defense_type,
                'scheduled_date' => $request->scheduled_date,
                'scheduled_time' => $request->scheduled_time,
                'room' => $request->room,
                'status' => 'scheduled',
                'coordinator_notes' => $request->coordinator_notes,
            ]);
            $this->createDefensePanel($defenseSchedule, $request);
            $defenseRequest->update([
                'status' => 'scheduled',
                'coordinator_notes' => $request->coordinator_notes,
                'responded_at' => now(),
            ]);
            $this->sendPanelNotifications($defenseSchedule);
            return redirect()->route('coordinator.defense.index')
                ->with('success', 'Defense scheduled successfully! Faculty panelists have been notified.');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while scheduling the defense.');
        }
    }
    public function editSchedule(DefenseSchedule $defenseSchedule)
    {
        $availableFaculty = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->get();
        return view('coordinator.defense-requests.edit-schedule', compact('defenseSchedule', 'availableFaculty'));
    }
    public function updateSchedule(Request $request, DefenseSchedule $defenseSchedule)
    {
        $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'room' => 'required|string|max:255',
            'coordinator_notes' => 'nullable|string|max:1000',
        ]);
        try {
            $defenseSchedule->update([
                'scheduled_date' => $request->scheduled_date,
                'scheduled_time' => $request->scheduled_time,
                'room' => $request->room,
                'coordinator_notes' => $request->coordinator_notes,
            ]);
            return redirect()->route('coordinator.defense.index')
                ->with('success', 'Defense schedule updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while updating the defense schedule.');
        }
    }
    public function approve(DefenseRequest $defenseRequest)
    {
        if (!$defenseRequest->isPending()) {
            return back()->with('error', 'This defense request cannot be approved.');
        }
        $defenseRequest->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);
        return back()->with('success', 'Defense request approved successfully!');
    }
    public function reject(Request $request, DefenseRequest $defenseRequest)
    {
        $request->validate([
            'coordinator_notes' => 'required|string|max:1000',
        ]);
        if (!$defenseRequest->isPending()) {
            return back()->with('error', 'This defense request cannot be rejected.');
        }
        $defenseRequest->update([
            'status' => 'rejected',
            'coordinator_notes' => $request->coordinator_notes,
            'responded_at' => now(),
        ]);
        return back()->with('success', 'Defense request rejected successfully!');
    }
    private function createDefensePanel(DefenseSchedule $defenseSchedule, Request $request)
    {
        $panelRoles = [
            'adviser' => $request->adviser_id,
            'subject_coordinator' => $request->subject_coordinator_id,
            'panelist_1' => $request->panelist_1_id,
            'panelist_2' => $request->panelist_2_id,
        ];
        foreach ($panelRoles as $role => $facultyId) {
            DefensePanel::create([
                'defense_schedule_id' => $defenseSchedule->id,
                'faculty_id' => $facultyId,
                'role' => $role,
                'status' => 'pending',
            ]);
        }
    }
    private function sendPanelNotifications(DefenseSchedule $defenseSchedule)
    {
        $panelists = $defenseSchedule->defensePanels()->whereIn('role', ['panelist_1', 'panelist_2'])->get();
        foreach ($panelists as $panelist) {
            Notification::create([
                'title' => 'Defense Panel Assignment',
                'description' => 'You have been assigned to a defense panel for ' . $defenseSchedule->group->name,
                'role' => 'panelist',
                'redirect_url' => route('coordinator.defense.index'),
            ]);
        }
    }
}
