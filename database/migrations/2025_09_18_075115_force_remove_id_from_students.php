<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Force remove the id column from students table
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE students DROP COLUMN id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the id column
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE students ADD COLUMN id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST');
    }
};
