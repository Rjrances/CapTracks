<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Event;
use App\Models\Notification;

class CoordinatorDashboardController extends Controller
{
    // Dashboard page for coordinator
    public function index()
    {
        $studentCount = Student::count();
        $recentStudents = Student::latest()->take(5)->get();

        // Fetch upcoming events (for example)
        $events = Event::where('date', '>=', now())
                       ->orderBy('date')
                       ->take(5)
                       ->get();

        // Fetch latest notifications
        $notifications = Notification::latest()->take(5)->get();

        return view('coordinator.dashboard', compact('studentCount', 'recentStudents', 'events', 'notifications'));
    }
}
