<?php

namespace App\Modules\Document\Imports;

use App\Modules\Document\Enums\DocumentStatusEnum;
use App\Modules\Document\Models\Document;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DocumentsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Document([
            'so_ky_hieu' => $row['so_ky_hieu'] ?? null,
            'ten_van_ban' => $row['ten_van_ban'] ?? null,
            'noi_dung' => $row['noi_dung'] ?? null,
            'status' => $row['status'] ?? DocumentStatusEnum::Active->value,
        ]);
    }
}
