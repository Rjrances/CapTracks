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
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $upcomingDefenses = DefenseSchedule::with(['group', 'defensePanels.faculty'])
            ->where('start_at', '>=', now()->toDateString())
            ->where('start_at', '<=', now()->addDays(30)->toDateString())
            ->where('status', 'scheduled')
            ->orderBy('start_at')
            ->limit(5)
            ->get();
        $notifications = Notification::where('role', 'chairperson')
                                     ->latest()
                                     ->take(5)
                                     ->get();
        $stats = [
            'activeProjects' => Group::whereHas('adviser')
                ->when($activeTerm, function($query) use ($activeTerm) {
                    return $query->where('academic_term_id', $activeTerm->id);
                })->count(),
            'facultyCount' => User::whereIn('role', ['adviser', 'panelist', 'teacher', 'coordinator', 'chairperson'])
                ->when($activeTerm, function($query) use ($activeTerm) {
                    return $query->where('semester', $activeTerm->semester);
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
