<?php

namespace App\Modules\Document\Imports;

use App\Modules\Document\Enums\DocumentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CatalogImport implements ToModel, WithHeadingRow
{
    public function __construct(
        protected string $modelClass
    ) {}

    public function model(array $row)
    {
        /** @var Model $model */
        $model = app($this->modelClass);

        return $model->newInstance([
            'name' => $row['name'] ?? null,
            'description' => $row['description'] ?? null,
            'status' => $row['status'] ?? DocumentStatusEnum::Active->value,
        ]);
    }
}
