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
        Schema::table('defense_panels', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending')->after('role');
            $table->timestamp('responded_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('defense_panels', function (Blueprint $table) {
            $table->dropColumn(['status', 'responded_at']);
        });
    }
};
