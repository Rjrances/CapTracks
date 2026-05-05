<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_evaluation_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_schedule_id')
                ->constrained('defense_schedules')
                ->cascadeOnDelete();
            $table->foreignId('group_id')
                ->constrained('groups')
                ->cascadeOnDelete();
            $table->foreignId('finalized_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->decimal('final_average_score', 8, 2)->default(0);
            $table->string('final_recommendation', 30);
            $table->text('final_notes')->nullable();
            $table->timestamp('finalized_at');
            $table->timestamps();

            $table->unique('defense_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_evaluation_summaries');
    }
};

