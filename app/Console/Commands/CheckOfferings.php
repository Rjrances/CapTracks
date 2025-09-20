<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Offering;

class CheckOfferings extends Command
{
    protected $signature = 'check:offerings';
    protected $description = 'Check offerings and their offer codes';

    public function handle()
    {
        $offerings = Offering::all(['subject_code', 'offer_code']);
        
        $this->info('Offerings in database:');
        foreach ($offerings as $offering) {
            $this->line($offering->subject_code . ' - ' . ($offering->offer_code ?? 'NULL'));
        }
        
        return 0;
    }
}