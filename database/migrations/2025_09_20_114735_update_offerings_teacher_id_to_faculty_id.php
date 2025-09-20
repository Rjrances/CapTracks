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
        Schema::table('offerings', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['teacher_id']);
            
            // Change teacher_id to faculty_id (string)
            $table->dropColumn('teacher_id');
            $table->string('faculty_id', 20)->nullable()->after('offer_code');
            
            // Add foreign key constraint to users.faculty_id
            $table->foreign('faculty_id')->references('faculty_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offerings', function (Blueprint $table) {
            // Drop the faculty_id foreign key
            $table->dropForeign(['faculty_id']);
            $table->dropColumn('faculty_id');
            
            // Add back teacher_id as foreignId
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }
};