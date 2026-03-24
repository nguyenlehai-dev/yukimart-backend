<?php

namespace App\Modules\Core\Imports;

use App\Modules\Core\Models\Role;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RolesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $guard = $row['guard_name'] ?? config('auth.defaults.guard', 'web');
        $organizationId = isset($row['organization_id']) ? (int) $row['organization_id'] : null;

        return new Role([
            'name' => $row['name'] ?? $row['name_'] ?? '',
            'guard_name' => $guard,
            'organization_id' => $organizationId ?: null,
        ]);
    }
}
