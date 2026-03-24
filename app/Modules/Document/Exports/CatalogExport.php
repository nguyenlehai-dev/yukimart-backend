<?php

namespace App\Modules\Document\Exports;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CatalogExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected string $modelClass,
        protected array $filters = []
    ) {}

    public function collection()
    {
        /** @var Model $model */
        $model = app($this->modelClass);

        return $model->newQuery()
            ->with(['creator', 'editor'])
            ->filter($this->filters)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'status' => $item->status,
                'created_by' => $item->creator?->name ?? 'N/A',
                'updated_by' => $item->editor?->name ?? 'N/A',
                'created_at' => $item->created_at?->format('H:i:s d/m/Y'),
                'updated_at' => $item->updated_at?->format('H:i:s d/m/Y'),
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Description', 'Status', 'Created By', 'Updated By', 'Created At', 'Updated At'];
    }
}
