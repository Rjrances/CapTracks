<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class CoordinatorController extends Controller
{
    public function index()
    {
        $events = Event::whereDate('date', '>=', now())->orderBy('date')->get();
        $notifications = Notification::latest()->take(5)->get();

        return view('coordinator.dashboard', compact('events', 'notifications'));
    }

    /**
     * Show classlist page with semester selection and students for selected semester
     */
    public function classlist(Request $request)
    {
        // Get all distinct semesters from students table
        $semesters = DB::table('students')
            ->select('semester')
            ->distinct()
            ->orderBy('semester', 'desc')
            ->pluck('semester');

        // Get selected semester from query string, default to first semester if not provided
        $selectedSemester = $request->input('semester') ?? $semesters->first();

        $students = collect(); // default to empty collection

        // If a semester is selected, get paginated students for that semester
        if ($selectedSemester) {
            $studentsQuery = DB::table('students')
                ->where('semester', $selectedSemester);
            if ($request->filled('search')) {
                $search = $request->input('search');
                $studentsQuery->where(function($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('student_id', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%" );
                });
            }
            $students = $studentsQuery->paginate(10)->appends($request->only(['semester', 'search']));
        }

        return view('coordinator.classlist.index', compact('semesters', 'students', 'selectedSemester'));
    }

    public function groups(Request $request)
    {
        $query = \App\Models\Group::with(['adviser', 'members']);
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%" );
            });
        }
        $groups = $query->paginate(10)->appends($request->only('search'));
        return view('coordinator.groups.index', compact('groups'));
    }

    public function create()
    {
        return view('coordinator.groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        \App\Models\Group::create($validated);

        return redirect()->route('coordinator.groups.index')->with('success', 'Group created successfully!');
    }

    public function show($id)
    {
        $group = \App\Models\Group::with(['adviser', 'members'])->findOrFail($id);
        return view('coordinator.groups.show', compact('group'));
    }

    public function edit($id)
    {
        $group = \App\Models\Group::findOrFail($id);
        return view('coordinator.groups.edit', compact('group'));
    }

    public function assignAdviser($id)
    {
        $group = \App\Models\Group::with(['adviser', 'members'])->findOrFail($id);
        return view('coordinator.groups.assign_adviser', compact('group'));
    }

    public function update(Request $request, $id)
    {
        $group = \App\Models\Group::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->update($validated);

        return redirect()->route('coordinator.groups.show', $group->id)->with('success', 'Group updated successfully!');
    }

    public function destroy($id)
    {
        $group = \App\Models\Group::findOrFail($id);
        
        // Delete related records first (due to foreign key constraints)
        $group->members()->detach(); // Remove all group members
        $group->adviserInvitations()->delete(); // Delete adviser invitations
        
        // Delete the group
        $group->delete();

        return redirect()->route('coordinator.groups.index')->with('success', 'Group deleted successfully!');
    }

    public function groupMilestones($id)
    {
        $group = \App\Models\Group::findOrFail($id);
        // Placeholder: fetch milestones if implemented
        return view('coordinator.groups.milestones', compact('group'));
    }

    public function events()
    {
        $events = \App\Models\Event::orderBy('date', 'desc')->get();
        return view('coordinator.events.index', compact('events'));
    }

    public function defenseScheduling()
    {
        // Placeholder: implement defense scheduling logic
        return view('coordinator.defense.scheduling');
    }

    public function notifications()
    {
        $notifications = \App\Models\Notification::orderBy('created_at', 'desc')->get();
        return view('coordinator.notifications', compact('notifications'));
    }
}
