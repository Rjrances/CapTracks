<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_rubric_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('stage', 20);
            $table->boolean('is_active')->default(false);
            $table->text('description')->nullable();
            $table->json('grade_guidelines')->nullable();
            $table->timestamps();

            $table->index(['stage', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_rubric_templates');
    }
};

