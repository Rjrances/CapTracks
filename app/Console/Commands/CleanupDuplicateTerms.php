<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicTerm;

class CleanupDuplicateTerms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'terms:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate academic terms and keep only the latest ones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up duplicate academic terms...');
        
        // Get all terms grouped by semester
        $terms = AcademicTerm::all()->groupBy('semester');
        
        $duplicatesRemoved = 0;
        
        foreach ($terms as $semester => $termGroup) {
            if ($termGroup->count() > 1) {
                $this->warn("Found {$termGroup->count()} duplicates for semester: {$semester}");
                
                // Keep the latest one (highest ID) and delete the rest
                $latestTerm = $termGroup->sortByDesc('id')->first();
                $duplicates = $termGroup->where('id', '!=', $latestTerm->id);
                
                foreach ($duplicates as $duplicate) {
                    $duplicate->delete();
                    $duplicatesRemoved++;
                    $this->line("Deleted duplicate term ID: {$duplicate->id}");
                }
                
                $this->info("Kept term ID: {$latestTerm->id} for semester: {$semester}");
            }
        }
        
        if ($duplicatesRemoved > 0) {
            $this->info("✅ Cleanup completed! Removed {$duplicatesRemoved} duplicate terms.");
        } else {
            $this->info("✅ No duplicates found. All terms are unique.");
        }
        
        // Show current terms
        $this->info("\nCurrent academic terms:");
        $currentTerms = AcademicTerm::all();
        foreach ($currentTerms as $term) {
            $status = $term->is_active ? '🟢 Active' : '⚪ Inactive';
            $this->line("- {$term->semester} {$status}");
        }
    }
}