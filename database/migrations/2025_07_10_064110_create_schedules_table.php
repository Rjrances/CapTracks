<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('schedules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('offering_id')->constrained('offerings')->onDelete('cascade');
        $table->string('day');          // e.g., Monday, Tue-Thu, etc.
        $table->string('start_time');   // or $table->time('start_time');
        $table->string('end_time');     // or $table->time('end_time');
        $table->string('room');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
