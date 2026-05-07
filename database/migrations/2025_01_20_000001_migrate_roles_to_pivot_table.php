<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing roles to pivot table
        $users = DB::table('users')->whereNotNull('role')->get();
        
        foreach ($users as $user) {
            if ($user->role) {
                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Keep users.role for backward compatibility with existing controllers/seeders.
        // Roles are still mirrored into user_roles for pivot-based checks.
    }

    public function down(): void
    {
        // Migrate data back from pivot table into users.role (column is retained in up()).
        $userRoles = DB::table('user_roles')->get();
        
        foreach ($userRoles as $userRole) {
            DB::table('users')
                ->where('id', $userRole->user_id)
                ->update(['role' => $userRole->role]);
        }
    }
};
