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
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'redirect_url')) {
                $table->string('redirect_url')->nullable()->after('role');
            }
            if (!Schema::hasColumn('notifications', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('redirect_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'redirect_url')) {
                $table->dropColumn('redirect_url');
            }
            if (Schema::hasColumn('notifications', 'is_read')) {
                $table->dropColumn('is_read');
            }
        });
    }
};
