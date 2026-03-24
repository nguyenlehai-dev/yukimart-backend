<?php

namespace App\Modules\Purchase\Exports;

use App\Modules\Purchase\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseOrderExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private array $filters = [],
    ) {}

    public function query()
    {
        return PurchaseOrder::with(['supplier:id,code,name', 'organization:id,name', 'creator:id,name'])
            ->filter($this->filters);
    }

    public function headings(): array
    {
        return ['Ma phieu', 'NCC', 'Chi nhanh', 'Tong tien', 'Giam gia', 'Da tra', 'Cong no', 'Trang thai', 'Ngay nhap', 'Nguoi tao'];
    }

    public function map($o): array
    {
        return [
            $o->code,
            $o->supplier?->name ?? '',
            $o->organization?->name ?? '',
            $o->total_amount,
            $o->discount,
            $o->paid_amount,
            $o->debt_amount,
            $o->status,
            $o->order_date?->format('d/m/Y H:i'),
            $o->creator?->name ?? '',
        ];
    }
}
