<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = (bool) config('permission.teams');

        $rolesTable = $tableNames['roles'] ?? 'roles';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $permissionPivotKey = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'organization_id';

        if (! Schema::hasTable($rolesTable)) {
            return;
        }

        // Gộp các role trùng tên/guard giữa nhiều organization về một role global.
        $roles = DB::table($rolesTable)
            ->select('id', 'name', 'guard_name')
            ->orderBy('id')
            ->get();

        $canonicalByKey = [];
        $idMap = [];
        $duplicateIds = [];

        foreach ($roles as $role) {
            $key = $role->name.'|'.$role->guard_name;
            if (! isset($canonicalByKey[$key])) {
                $canonicalByKey[$key] = (int) $role->id;
            }

            $canonicalId = $canonicalByKey[$key];
            $idMap[(int) $role->id] = $canonicalId;

            if ($canonicalId !== (int) $role->id) {
                $duplicateIds[] = (int) $role->id;
            }
        }

        if (! empty($duplicateIds)) {
            $modelRoleRows = DB::table($modelHasRolesTable)
                ->select([$teamForeignKey, $rolePivotKey, 'model_type', $modelMorphKey])
                ->get();

            $normalizedModelRoleRows = [];
            foreach ($modelRoleRows as $row) {
                $mappedRoleId = $idMap[(int) $row->{$rolePivotKey}] ?? (int) $row->{$rolePivotKey};
                $signature = implode('|', [
                    (int) $row->{$teamForeignKey},
                    $mappedRoleId,
                    $row->model_type,
                    (int) $row->{$modelMorphKey},
                ]);

                $normalizedModelRoleRows[$signature] = [
                    $teamForeignKey => (int) $row->{$teamForeignKey},
                    $rolePivotKey => $mappedRoleId,
                    'model_type' => $row->model_type,
                    $modelMorphKey => (int) $row->{$modelMorphKey},
                ];
            }

            DB::table($modelHasRolesTable)->delete();
            foreach (array_chunk(array_values($normalizedModelRoleRows), 1000) as $chunk) {
                DB::table($modelHasRolesTable)->insert($chunk);
            }

            $rolePermissionRows = DB::table($roleHasPermissionsTable)
                ->select([$permissionPivotKey, $rolePivotKey])
                ->get();

            $normalizedRolePermissionRows = [];
            foreach ($rolePermissionRows as $row) {
                $mappedRoleId = $idMap[(int) $row->{$rolePivotKey}] ?? (int) $row->{$rolePivotKey};
                $signature = implode('|', [(int) $row->{$permissionPivotKey}, $mappedRoleId]);

                $normalizedRolePermissionRows[$signature] = [
                    $permissionPivotKey => (int) $row->{$permissionPivotKey},
                    $rolePivotKey => $mappedRoleId,
                ];
            }

            DB::table($roleHasPermissionsTable)->delete();
            foreach (array_chunk(array_values($normalizedRolePermissionRows), 1000) as $chunk) {
                DB::table($roleHasPermissionsTable)->insert($chunk);
            }

            DB::table($rolesTable)->whereIn('id', $duplicateIds)->delete();
        }

        if (Schema::hasColumn($rolesTable, $teamForeignKey)) {
            DB::table($rolesTable)->update([$teamForeignKey => null]);
        }

        if ($teams) {
            Schema::table($rolesTable, function (Blueprint $table) use ($teamForeignKey) {
                $table->dropUnique([$teamForeignKey, 'name', 'guard_name']);
            });

            Schema::table($rolesTable, function (Blueprint $table) {
                $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
            });
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = (bool) config('permission.teams');

        if (! $teams) {
            return;
        }

        $rolesTable = $tableNames['roles'] ?? 'roles';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'organization_id';

        if (! Schema::hasTable($rolesTable)) {
            return;
        }

        Schema::table($rolesTable, function (Blueprint $table) {
            $table->dropUnique('roles_name_guard_name_unique');
        });

        Schema::table($rolesTable, function (Blueprint $table) use ($teamForeignKey) {
            $table->unique([$teamForeignKey, 'name', 'guard_name']);
        });
    }
};
