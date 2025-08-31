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
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->string('title')->nullable()->after('id');
            $table->text('objectives')->nullable()->after('title');
            $table->text('methodology')->nullable()->after('objectives');
            $table->text('timeline')->nullable()->after('methodology');
            $table->text('expected_outcomes')->nullable()->after('timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->dropColumn(['title', 'objectives', 'methodology', 'timeline', 'expected_outcomes']);
        });
    }
};
