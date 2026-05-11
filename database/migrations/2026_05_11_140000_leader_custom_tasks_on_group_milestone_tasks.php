<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            $table->dropForeign(['milestone_task_id']);
        });

        DB::statement('ALTER TABLE group_milestone_tasks MODIFY milestone_task_id BIGINT UNSIGNED NULL');

        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            $table->foreign('milestone_task_id')
                ->references('id')
                ->on('milestone_tasks')
                ->cascadeOnDelete();
        });

        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            $table->string('custom_title', 255)->nullable()->after('milestone_task_id');
            $table->text('custom_description')->nullable()->after('custom_title');
        });
    }

    public function down(): void
    {
        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            $table->dropColumn(['custom_title', 'custom_description']);
        });

        DB::table('group_milestone_tasks')->whereNull('milestone_task_id')->delete();

        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            $table->dropForeign(['milestone_task_id']);
        });

        DB::statement('ALTER TABLE group_milestone_tasks MODIFY milestone_task_id BIGINT UNSIGNED NOT NULL');

        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            $table->foreign('milestone_task_id')
                ->references('id')
                ->on('milestone_tasks')
                ->cascadeOnDelete();
        });
    }
};
