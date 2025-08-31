<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Notification;
use App\Models\DefenseSchedule;
use App\Models\Group;
use App\Models\User;
use App\Models\Offering;
use App\Models\AcademicTerm;

class ChairpersonDashboardController extends Controller
{
    public function index()
    {
        // Get current active term
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        // Get upcoming defense schedules
        $upcomingDefenses = DefenseSchedule::with(['group', 'defensePanels.faculty'])
            ->where('start_at', '>=', now()->toDateString())
            ->where('start_at', '<=', now()->addDays(30)->toDateString())
            ->where('status', 'scheduled')
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        // Fetch latest notifications for chairperson
        $notifications = Notification::where('role', 'chairperson')
                                     ->latest()
                                     ->take(5)
                                     ->get();



        // Dashboard statistics
        $stats = [
            'activeProjects' => Group::whereHas('adviser')->count(),
            'facultyCount' => User::whereHas('roles', function($query) {
                $query->whereIn('name', ['adviser', 'panelist']);
            })->count(),
            'pendingReviews' => DefenseSchedule::where('status', 'scheduled')->count(),
            'offeringsCount' => Offering::when($activeTerm, function($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })->count(),
            'totalDefenses' => DefenseSchedule::count(),
            'completedDefenses' => DefenseSchedule::where('status', 'completed')->count(),
        ];

        return view('chairperson.dashboard', compact(
            'activeTerm',
            'upcomingDefenses',
            'notifications',
            'stats'
        ));
    }
}
