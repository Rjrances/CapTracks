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
        // Check if offer_code column already exists
        if (!Schema::hasColumn('offerings', 'offer_code')) {
            Schema::table('offerings', function (Blueprint $table) {
                // Add offer_code column (nullable first, then we'll populate it)
                $table->string('offer_code')->nullable()->after('id');
            });
        }
        
        // Populate existing records with offer_code based on their current data
        $offerings = \App\Models\Offering::all();
        foreach ($offerings as $index => $offering) {
            if (empty($offering->offer_code)) {
                $offering->offer_code = '120' . ($index + 1); // Generate unique offer codes
                $offering->save();
            }
        }
        
        // Now make it unique and not null
        Schema::table('offerings', function (Blueprint $table) {
            $table->string('offer_code')->unique()->change();
            
            // Add index only if it doesn't exist
            if (!Schema::hasIndex('offerings', 'offerings_offer_code_index')) {
                $table->index('offer_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offerings', function (Blueprint $table) {
            // Remove offer_code
            $table->dropIndex(['offer_code']);
            $table->dropColumn('offer_code');
        });
    }
};
