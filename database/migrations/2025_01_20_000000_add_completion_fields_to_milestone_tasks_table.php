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
        Schema::table('milestone_tasks', function (Blueprint $table) {
            $table->boolean('is_completed')->default(false)->after('order');
            $table->timestamp('completed_at')->nullable()->after('is_completed');
            $table->string('assigned_to')->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestone_tasks', function (Blueprint $table) {
            $table->dropColumn(['is_completed', 'completed_at', 'assigned_to']);
        });
    }
}; 