<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove seeded placeholder text left on groups.description before it was dropped from DefenseDatasetSeeder.
     */
    public function up(): void
    {
        DB::table('groups')
            ->where('description', 'Defense dry-run group')
            ->update(['description' => null, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Intentionally empty: do not restore placeholder copy.
    }
};
