<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added missing import for DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, drop the existing enum constraint
        DB::statement("ALTER TABLE user_roles MODIFY COLUMN role ENUM('chairperson', 'coordinator', 'adviser', 'panelist', 'teacher')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE user_roles MODIFY COLUMN role ENUM('chairperson', 'coordinator', 'adviser', 'panelist')");
    }
};
