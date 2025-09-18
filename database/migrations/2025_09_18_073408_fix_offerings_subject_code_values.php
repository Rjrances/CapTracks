<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix subject_code values to use proper codes (CT1, CT2, T1, T2)
        $offerings = \App\Models\Offering::all();
        
        foreach ($offerings as $offering) {
            // Map current subject_code values to proper codes
            $subjectCodeMap = [
                'CS 402' => 'CT2',  // Capstone 2
                'CS 301' => 'T1',   // Thesis 1
                '1201' => 'CT1',    // Capstone 1
            ];
            
            if (isset($subjectCodeMap[$offering->subject_code])) {
                $offering->subject_code = $subjectCodeMap[$offering->subject_code];
                $offering->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert subject_code values back to original
        $offerings = \App\Models\Offering::all();
        
        foreach ($offerings as $offering) {
            // Map back to original values
            $reverseMap = [
                'CT2' => 'CS 402',  // Capstone 2
                'T1' => 'CS 301',   // Thesis 1
                'CT1' => '1201',    // Capstone 1
            ];
            
            if (isset($reverseMap[$offering->subject_code])) {
                $offering->subject_code = $reverseMap[$offering->subject_code];
                $offering->save();
            }
        }
    }
};
