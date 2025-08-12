<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'teacher' to the allowed enum values of user_roles.role
        DB::statement("ALTER TABLE `user_roles` MODIFY COLUMN `role` ENUM('chairperson','coordinator','teacher','adviser','panelist') NOT NULL");
    }

    public function down(): void
    {
        // Revert back to the previous enum without 'teacher'
        DB::statement("ALTER TABLE `user_roles` MODIFY COLUMN `role` ENUM('chairperson','coordinator','adviser','panelist') NOT NULL");
    }
};


