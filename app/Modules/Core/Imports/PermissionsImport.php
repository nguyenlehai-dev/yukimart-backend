<?php

namespace App\Modules\Core\Imports;

use App\Modules\Core\Models\Permission;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PermissionsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $guard = $row['guard_name'] ?? config('auth.defaults.guard', 'web');
        $parentId = isset($row['parent_id']) && $row['parent_id'] !== '' ? (int) $row['parent_id'] : null;

        return new Permission([
            'name' => $row['name'] ?? $row['name_'] ?? '',
            'guard_name' => $guard,
            'description' => $row['description'] ?? null,
            'sort_order' => isset($row['sort_order']) ? (int) $row['sort_order'] : 0,
            'parent_id' => $parentId,
        ]);
    }
}
