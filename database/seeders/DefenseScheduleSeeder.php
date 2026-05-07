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
                'stage' => '60',
                'start_at' => Carbon::now()->addDays(7)->setTime(9, 0, 0),
                'room' => 'Room 101',
                'remarks' => 'First defense presentation for the semester',
            ],
            [
                'group_id' => $groups->count() > 1 ? $groups[1]->id : $groups->first()->id,
                'stage' => '100',
                'start_at' => Carbon::now()->addDays(14)->setTime(14, 0, 0),
                'room' => 'Room 102',
                'remarks' => 'Final defense presentation',
            ],
        ];

        foreach ($schedules as $scheduleData) {
            $startAt = Carbon::parse($scheduleData['start_at']);
            $defenseSchedule = DefenseSchedule::create([
                'group_id' => $scheduleData['group_id'],
                'stage' => $scheduleData['stage'],
                'academic_term_id' => $activeTerm->id,
                'start_at' => $startAt,
                'end_at' => $startAt->copy()->addHours(2),
                'room' => $scheduleData['room'],
                'remarks' => $scheduleData['remarks'],
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
