<?php

namespace App\Modules\Core\Middleware;

use App\Modules\Core\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $this->isSuperAdmin((int) $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền quản trị shop.',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }

    private function isSuperAdmin(int $userId): bool
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        return DB::table($tableNames['model_has_roles'] ?? 'model_has_roles')
            ->join($tableNames['roles'] ?? 'roles', ($tableNames['roles'] ?? 'roles').'.id', '=', ($tableNames['model_has_roles'] ?? 'model_has_roles').'.'.($columnNames['role_pivot_key'] ?? 'role_id'))
            ->where(($tableNames['model_has_roles'] ?? 'model_has_roles').'.'.($columnNames['model_morph_key'] ?? 'model_id'), $userId)
            ->where(($tableNames['model_has_roles'] ?? 'model_has_roles').'.model_type', User::class)
            ->where(($tableNames['roles'] ?? 'roles').'.name', 'Super Admin')
            ->exists();
    }
}
