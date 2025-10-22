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
        Schema::table('group_milestones', function (Blueprint $table) {
            $table->string('title')->nullable()->after('milestone_template_id');
            $table->text('description')->nullable()->after('title');
            $table->date('due_date')->nullable()->after('target_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_milestones', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'due_date']);
        });
    }
};
