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
        Schema::table('students', function (Blueprint $table) {
            $table->string('offer_code', 20)->nullable()->after('semester');
            
            // Add foreign key constraint to offerings table
            $table->foreign('offer_code')->references('offer_code')->on('offerings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['offer_code']);
            $table->dropColumn('offer_code');
        });
    }
};