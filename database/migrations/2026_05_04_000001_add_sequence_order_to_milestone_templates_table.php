<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('sequence_order')->nullable()->after('status');
        });

        // Default ordering for standard capstone phases (matches typical template names).
        $map = [
            ['Proposal Documents', 1],
            ['60% Defense', 2],
            ['100% Defense', 3],
        ];
        foreach ($map as [$name, $order]) {
            DB::table('milestone_templates')->where('name', $name)->update(['sequence_order' => $order]);
        }
    }

    public function down(): void
    {
        Schema::table('milestone_templates', function (Blueprint $table) {
            $table->dropColumn('sequence_order');
        });
    }
};
