<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckAndRestoreData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:restore-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and restore missing data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking and restoring data...');
        
        // Check students
        $students = \App\Models\Student::all();
        $this->line("Students count: {$students->count()}");
        
        // Check offerings
        $offerings = \App\Models\Offering::all();
        $this->line("Offerings count: {$offerings->count()}");
        
        // Check offering_student relationships
        $offeringStudentCount = \Illuminate\Support\Facades\DB::table('offering_student')->count();
        $this->line("Offering-Student relationships: {$offeringStudentCount}");
        
        if ($offeringStudentCount == 0 && $students->count() > 0 && $offerings->count() > 0) {
            $this->line('Restoring offering-student relationships...');
            
            // Enroll all students in the first offering (or create a default offering)
            $defaultOffering = $offerings->first();
            if (!$defaultOffering) {
                $this->error('No offerings found. Please create an offering first.');
                return;
            }
            
            foreach ($students as $student) {
                \Illuminate\Support\Facades\DB::table('offering_student')->insert([
                    'offering_id' => $defaultOffering->id,
                    'student_id' => $student->student_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            $this->info('Restored offering-student relationships!');
        }
        
        // Check group_members
        $groupMembersCount = \Illuminate\Support\Facades\DB::table('group_members')->count();
        $this->line("Group members: {$groupMembersCount}");
        
        $this->info('Data check complete!');
    }
}
