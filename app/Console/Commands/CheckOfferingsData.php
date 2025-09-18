<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckOfferingsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:offerings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check offerings data with offer_code';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking offerings data...');
        
        $offerings = \App\Models\Offering::all();
        
        foreach ($offerings as $offering) {
            $this->line("ID: {$offering->id}, Offer Code: {$offering->offer_code}, Subject Code: {$offering->subject_code}, Title: {$offering->subject_title}");
        }
        
        $this->info('Total offerings: ' . $offerings->count());
    }
}
