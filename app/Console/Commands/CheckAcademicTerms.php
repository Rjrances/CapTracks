<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicTerm;

class CheckAcademicTerms extends Command
{
    protected $signature = 'check:academic-terms';
    protected $description = 'Check academic terms';

    public function handle()
    {
        $terms = AcademicTerm::all();
        
        $this->info('Academic Terms in database:');
        foreach ($terms as $term) {
            $this->line($term->name . ' - Active: ' . ($term->is_active ? 'Yes' : 'No'));
        }
        
        return 0;
    }
}