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
        Schema::table('users', function (Blueprint $table) {
            // First drop the position column
            $table->dropColumn('position');
            
            // Add the role column with enum values
            $table->enum('role', ['chairperson', 'coordinator', 'adviser', 'panelist', 'teacher'])->after('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the role column
            $table->dropColumn('role');
            
            // Add back the position column
            $table->string('position')->nullable()->after('department');
        });
    }
};
