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
        // Update existing offercodes to start at 110
        $offerings = \App\Models\Offering::orderBy('id')->get();
        foreach ($offerings as $index => $offering) {
            $offering->offer_code = '110' . ($index + 1); // Generate offer codes starting at 1101, 1102, etc.
            $offering->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to 120 series
        $offerings = \App\Models\Offering::orderBy('id')->get();
        foreach ($offerings as $index => $offering) {
            $offering->offer_code = '120' . ($index + 1);
            $offering->save();
        }
    }
};
