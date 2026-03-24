<?php

namespace App\Modules\Purchase\Exports;

use App\Modules\Purchase\Models\PurchaseReturn;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseReturnExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private array $filters = [],
    ) {}

    public function query()
    {
        return PurchaseReturn::with(['supplier:id,code,name', 'organization:id,name', 'creator:id,name'])
            ->filter($this->filters);
    }

    public function headings(): array
    {
        return ['Ma phieu', 'Nha cung cap', 'Chi nhanh', 'Tong tien', 'NCC tra', 'Cong no', 'Trang thai', 'Ngay tra', 'Nguoi tao'];
    }

    public function map($r): array
    {
        return [
            $r->code,
            $r->supplier?->name ?? '',
            $r->organization?->name ?? '',
            $r->total_amount,
            $r->supplier_paid,
            $r->debt_amount,
            $r->status,
            $r->return_date?->format('d/m/Y H:i'),
            $r->creator?->name ?? '',
        ];
    }
}
