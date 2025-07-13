<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offerings', function (Blueprint $table) {
            $table->id();
            $table->string('subject_title');   // Subject Title
            $table->string('subject_code');    // Offer Code
            $table->string('teacher_name');    // Teacher Name
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offerings');
    }
};
