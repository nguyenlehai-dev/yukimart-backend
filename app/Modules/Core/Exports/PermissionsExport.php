<?php

namespace App\Modules\Core\Exports;

use App\Modules\Core\Models\Permission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PermissionsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection()
    {
        $items = Permission::filter($this->filters)->get();

        return $items->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'guard_name' => $p->guard_name,
            'description' => $p->description ?? '',
            'sort_order' => $p->sort_order ?? 0,
            'parent_id' => $p->parent_id ?? '',
            'created_at' => $p->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $p->updated_at?->format('H:i:s d/m/Y'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Guard Name', 'Description', 'Sort Order', 'Parent ID', 'Created At', 'Updated At'];
    }
}
