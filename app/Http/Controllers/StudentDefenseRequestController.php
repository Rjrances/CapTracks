<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\DefenseRequest;
use App\Models\Group;
use App\Models\MilestoneTemplate;
use Illuminate\Support\Facades\Auth;
class StudentDefenseRequestController extends Controller
{
    public function index()
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.group')->withErrors(['group' => 'You must be part of a group to request defenses.']);
        }
        $defenseRequests = DefenseRequest::where('group_id', $group->id)
            ->with(['group', 'defenseSchedule.defensePanels.faculty'])
            ->orderBy('created_at', 'desc')
            ->get();
        return view('student.defense-requests.index', compact('defenseRequests', 'group'));
    }
    public function create(Request $request)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.group')->withErrors(['group' => 'You must be part of a group to request defenses.']);
        }
        if (!$group->faculty_id) {
            return redirect()->route('student.group')->withErrors(['adviser' => 'Your group must have an adviser before requesting a defense.']);
        }
        $milestoneTemplates = MilestoneTemplate::where('status', 'active')->get();
        $pendingRequests = DefenseRequest::where('group_id', $group->id)
            ->where('status', 'pending')
            ->count();
        if ($pendingRequests > 0) {
            return redirect()->route('student.defense-requests.index')
                ->withErrors(['pending' => 'You already have a pending defense request. Please wait for coordinator response.']);
        }
        
        // Get defense type from URL parameter
        $defenseType = $request->get('defense_type', 'proposal');
        
        return view('student.defense-requests.create', compact('group', 'milestoneTemplates', 'defenseType'));
    }
    public function store(Request $request)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.group')->withErrors(['group' => 'You must be part of a group to request defenses.']);
        }
        $request->validate([
            'defense_type' => 'required|in:proposal,60_percent,100_percent',
            'student_message' => 'nullable|string|max:1000',
            'preferred_date' => 'required|date|after:today',
            'preferred_time' => 'required|date_format:H:i',
        ]);
        $pendingRequests = DefenseRequest::where('group_id', $group->id)
            ->where('status', 'pending')
            ->count();
        if ($pendingRequests > 0) {
            return redirect()->route('student.defense-requests.index')
                ->withErrors(['pending' => 'You already have a pending defense request. Please wait for coordinator response.']);
        }
        try {
            DefenseRequest::create([
                'group_id' => $group->id,
                'defense_type' => $request->defense_type,
                'student_message' => $request->student_message,
                'status' => 'pending',
                'requested_at' => now(),
            ]);
            return redirect()->route('student.defense-requests.index')
                ->with('success', 'Defense request submitted successfully! The coordinator will review your request.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to submit defense request. Please try again.']);
        }
    }
    public function show(DefenseRequest $defenseRequest)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group || $defenseRequest->group_id !== $group->id) {
            abort(403, 'Unauthorized access to this defense request.');
        }
        $defenseRequest->load(['defenseSchedule.defensePanels.faculty']);
        return view('student.defense-requests.show', compact('defenseRequest'));
    }
    public function cancel(DefenseRequest $defenseRequest)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group || $defenseRequest->group_id !== $group->id) {
            abort(403, 'Unauthorized access to this defense request.');
        }
        if (!$defenseRequest->isPending()) {
            return back()->withErrors(['status' => 'Only pending requests can be cancelled.']);
        }
        try {
            $defenseRequest->delete();
            return redirect()->route('student.defense-requests.index')
                ->with('success', 'Defense request cancelled successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to cancel defense request.']);
        }
    }
    private function getAuthenticatedStudent()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            return $studentAccount->student;
        }
        return null;
    }
}
