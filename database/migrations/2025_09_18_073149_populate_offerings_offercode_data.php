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
        // Populate existing records with offer_code based on their current data
        $offerings = \App\Models\Offering::all();
        foreach ($offerings as $index => $offering) {
            if (empty($offering->offer_code)) {
                $offering->offer_code = '110' . ($index + 1); // Generate unique offer codes starting at 1101
                $offering->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear offer_code data
        \App\Models\Offering::query()->update(['offer_code' => null]);
    }
};
