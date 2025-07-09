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
    Schema::table('users', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->string('school_id')->unique()->after('id');
        $table->enum('role', ['student', 'adviser', 'panelist', 'coordinator', 'chairperson'])->default('student')->after('email');
    });
}

public function down()
{
    Schema::table('users', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->dropColumn('school_id');
        $table->dropColumn('role');
    });
}

};
