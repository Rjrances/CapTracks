<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE defense_panels MODIFY COLUMN role ENUM('chair','member','panelist','adviser','coordinator') NOT NULL DEFAULT 'member'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE defense_panels MODIFY COLUMN role ENUM('chair','member','adviser','coordinator') NOT NULL DEFAULT 'member'");
        }
    }
};
