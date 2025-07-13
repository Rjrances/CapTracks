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
            $students = DB::table('students')
                ->where('semester', $selectedSemester)
                ->paginate(10); // 👈 Paginate 10 students per page
        }

        return view('coordinator.classlist.index', compact('semesters', 'students', 'selectedSemester'));
    }
}
