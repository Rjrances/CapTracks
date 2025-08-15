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
        Schema::table('defense_requests', function (Blueprint $table) {
            // Drop old columns if they exist
            if (Schema::hasColumn('defense_requests', 'milestone_template_id')) {
                $table->dropForeign(['milestone_template_id']);
                $table->dropColumn('milestone_template_id');
            }
            if (Schema::hasColumn('defense_requests', 'requested_date')) {
                $table->dropColumn('requested_date');
            }
            if (Schema::hasColumn('defense_requests', 'requested_time')) {
                $table->dropColumn('requested_time');
            }
            
            // Add new columns if they don't exist
            if (!Schema::hasColumn('defense_requests', 'defense_type')) {
                $table->enum('defense_type', ['proposal', '60_percent', '100_percent'])->after('group_id');
            }
            if (!Schema::hasColumn('defense_requests', 'student_message')) {
                $table->text('student_message')->nullable()->after('status');
            }
            if (!Schema::hasColumn('defense_requests', 'requested_at')) {
                $table->timestamp('requested_at')->nullable()->after('student_message');
            }
            if (!Schema::hasColumn('defense_requests', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('requested_at');
            }
            
            // Update status enum if needed
            if (Schema::hasColumn('defense_requests', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected', 'scheduled'])->default('pending')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defense_requests', function (Blueprint $table) {
            // Revert changes
            $table->dropColumn(['defense_type', 'student_message', 'requested_at', 'responded_at']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
            
            // Re-add old columns
            $table->foreignId('milestone_template_id')->constrained()->onDelete('cascade');
            $table->date('requested_date');
            $table->time('requested_time');
        });
    }
};
