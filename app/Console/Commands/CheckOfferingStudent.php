<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckOfferingStudent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:offering-student';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check offering_student table data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking offering_student table...');
        
        $count = \Illuminate\Support\Facades\DB::table('offering_student')->count();
        $this->line("Rows in offering_student: {$count}");
        
        if ($count > 0) {
            $sample = \Illuminate\Support\Facades\DB::table('offering_student')->take(5)->get();
            $this->line("Sample data:");
            foreach ($sample as $row) {
                $this->line("  Offering: {$row->offering_id}, Student: {$row->student_id}");
            }
        }
        
        $this->info('Check complete!');
    }
}
