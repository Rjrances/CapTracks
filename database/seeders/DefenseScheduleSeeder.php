<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DefenseSchedule;
use App\Models\DefensePanel;
use App\Models\Group;
use App\Models\User;
use App\Models\AcademicTerm;
use Carbon\Carbon;

class DefenseScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active academic term
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        
        // Get groups with advisers
        $groups = Group::whereHas('adviser')->get();
        
        // Get faculty members
        $faculty = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->get();
        
        if ($groups->isEmpty() || $faculty->isEmpty() || !$activeTerm) {
            return;
        }

        // Create sample defense schedules
        $schedules = [
            [
                'group_id' => $groups->first()->id,
                'defense_type' => '60_percent',
                'scheduled_date' => Carbon::now()->addDays(7)->toDateString(),
                'scheduled_time' => '09:00:00',
                'room' => 'Room 101',
                'coordinator_notes' => 'First defense presentation for the semester',
            ],
            [
                'group_id' => $groups->count() > 1 ? $groups[1]->id : $groups->first()->id,
                'defense_type' => '100_percent',
                'scheduled_date' => Carbon::now()->addDays(14)->toDateString(),
                'scheduled_time' => '14:00:00',
                'room' => 'Room 102',
                'coordinator_notes' => 'Final defense presentation',
            ],
        ];

        foreach ($schedules as $scheduleData) {
            $defenseSchedule = DefenseSchedule::create([
                'group_id' => $scheduleData['group_id'],
                'defense_type' => $scheduleData['defense_type'],
                'scheduled_date' => $scheduleData['scheduled_date'],
                'scheduled_time' => $scheduleData['scheduled_time'],
                'room' => $scheduleData['room'],
                'coordinator_notes' => $scheduleData['coordinator_notes'],
                'status' => 'scheduled',
            ]);

            // Assign panelists (at least 3 per panel)
            $panelists = $faculty->random(min(3, $faculty->count()));
            $roles = ['chair', 'member', 'member'];
            
            foreach ($panelists as $index => $panelist) {
                DefensePanel::create([
                    'defense_schedule_id' => $defenseSchedule->id,
                    'faculty_id' => $panelist->id,
                    'role' => $roles[$index] ?? 'member',
                ]);
            }
        }
    }
}
