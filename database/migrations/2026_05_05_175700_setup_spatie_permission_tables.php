<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'guard_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('name');
            });
        }

        Role::query()
            ->whereNull('guard_name')
            ->orWhere('guard_name', '')
            ->update(['guard_name' => 'web']);

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign('permission_id')
                    ->references('id')
                    ->on('permissions')
                    ->onDelete('cascade');
                $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->onDelete('cascade');
                $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->foreign('permission_id')
                    ->references('id')
                    ->on('permissions')
                    ->onDelete('cascade');
                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->onDelete('cascade');
                $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        $this->syncLegacyUserRolesToSpatie();
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
    }

    private function syncLegacyUserRolesToSpatie(): void
    {
        $modelType = User::class;

        if (Schema::hasColumn('users', 'role')) {
            $users = User::query()->whereNotNull('role')->get(['id', 'role']);
            foreach ($users as $user) {
                if (empty($user->role)) {
                    continue;
                }

                $roleId = Role::query()->where('name', $user->role)->value('id');
                if (!$roleId) {
                    continue;
                }

                DB::table('model_has_roles')->updateOrInsert(
                    ['role_id' => $roleId, 'model_type' => $modelType, 'model_id' => $user->id],
                    []
                );
            }
        }

        if (Schema::hasTable('user_roles')) {
            $legacyRoles = DB::table('user_roles')->get(['user_id', 'role']);
            foreach ($legacyRoles as $legacyRole) {
                $roleId = Role::query()->where('name', $legacyRole->role)->value('id');
                if (!$roleId) {
                    continue;
                }

                DB::table('model_has_roles')->updateOrInsert(
                    ['role_id' => $roleId, 'model_type' => $modelType, 'model_id' => $legacyRole->user_id],
                    []
                );
            }
        }
    }
};
