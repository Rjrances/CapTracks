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
        Schema::table('groups', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['adviser_id']);
            $table->dropColumn('adviser_id');
        });
        
        Schema::table('groups', function (Blueprint $table) {
            // Add faculty_id as string column with foreign key to users.faculty_id
            $table->string('faculty_id', 20)->nullable()->after('description');
            $table->foreign('faculty_id')->references('faculty_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['faculty_id']);
            $table->dropColumn('faculty_id');
        });
        
        Schema::table('groups', function (Blueprint $table) {
            // Restore the original foreign key constraint
            $table->foreignId('adviser_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};