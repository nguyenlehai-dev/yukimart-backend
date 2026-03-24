<?php

namespace App\Modules\Inventory\Exports;

use App\Modules\Inventory\Models\StockDisposal;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockDisposalExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        return StockDisposal::with(['organization:id,name', 'creator:id,name'])->filter($this->filters);
    }

    public function headings(): array
    {
        return ['Ma phieu', 'Chi nhanh', 'Tong gia tri', 'Trang thai', 'Ngay huy', 'Ghi chu', 'Nguoi tao'];
    }

    public function map($d): array
    {
        return [$d->code, $d->organization?->name, $d->total_amount, $d->status, $d->disposal_date?->format('d/m/Y H:i'), $d->note ?? '', $d->creator?->name ?? ''];
    }
}
