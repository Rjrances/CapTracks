<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_rubric_template_id')
                ->constrained('defense_rubric_templates')
                ->cascadeOnDelete();
            $table->string('scope', 20)->default('group');
            $table->string('name');
            $table->decimal('max_points', 8, 2)->default(10);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['defense_rubric_template_id', 'scope', 'sort_order'], 'rubric_template_scope_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_rubric_criteria');
    }
};

