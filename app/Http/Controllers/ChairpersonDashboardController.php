<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Notification;

class ChairpersonDashboardController extends Controller
{
    public function index()
    {
        // Fetch upcoming events (for example)
        $events = Event::where('date', '>=', now())
                       ->orderBy('date')
                       ->take(5)
                       ->get();

        // Fetch latest notifications for chairperson
        $notifications = Notification::where('role', 'chairperson')
                                     ->latest()
                                     ->take(5)
                                     ->get();

        return view('chairperson.dashboard', compact('events', 'notifications'));
    }
}
