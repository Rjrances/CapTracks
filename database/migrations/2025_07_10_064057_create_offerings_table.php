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
    Schema::create('offerings', function (Blueprint $table) {
        $table->id();
        $table->string('subject_code');
        $table->string('subject_title');
        $table->string('section');
        $table->string('teacher_name'); // optional: use teacher_id if related to users table
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offerings');
    }
};
