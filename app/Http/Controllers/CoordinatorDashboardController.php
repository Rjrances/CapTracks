<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Notification;

class CoordinatorDashboardController extends Controller
{
    public function index()
{
    $events = Event::whereDate('date', '>=', now())->orderBy('date')->get();
    $notifications = Notification::latest()->take(5)->get();

    return view('coordinator.dashboard', compact('events', 'notifications'));
}
}
