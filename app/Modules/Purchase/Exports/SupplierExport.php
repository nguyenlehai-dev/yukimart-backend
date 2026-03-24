<?php

namespace App\Modules\Purchase\Exports;

use App\Modules\Purchase\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SupplierExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private array $filters = [],
    ) {}

    public function query()
    {
        return Supplier::with(['group:id,name'])->filter($this->filters);
    }

    public function headings(): array
    {
        return ['Ma NCC', 'Ten NCC', 'Cong ty', 'Nhom', 'SDT', 'Email', 'MST', 'Dia chi', 'Cong no', 'Trang thai'];
    }

    public function map($s): array
    {
        return [
            $s->code,
            $s->name,
            $s->company ?? '',
            $s->group?->name ?? '',
            $s->phone ?? '',
            $s->email ?? '',
            $s->tax_code ?? '',
            $s->address ?? '',
            $s->debt,
            $s->status,
        ];
    }
}
