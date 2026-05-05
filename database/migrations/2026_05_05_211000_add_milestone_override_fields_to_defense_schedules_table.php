<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defense_schedules', function (Blueprint $table) {
            $table->boolean('milestone_gate_overridden')->default(false)->after('status');
            $table->text('milestone_override_reason')->nullable()->after('milestone_gate_overridden');
        });
    }

    public function down(): void
    {
        Schema::table('defense_schedules', function (Blueprint $table) {
            $table->dropColumn(['milestone_gate_overridden', 'milestone_override_reason']);
        });
    }
};

