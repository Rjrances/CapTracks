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
    Schema::create('students', function (Blueprint $table) {
        $table->id();
        $table->string('student_id', 20)->unique();
        $table->string('name', 100);
        $table->string('email', 100)->unique();
        $table->string('semester', 10);
        $table->string('course', 50);
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('students');
}

};
