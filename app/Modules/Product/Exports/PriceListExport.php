<?php

namespace App\Modules\Product\Exports;

use App\Modules\Product\Models\PriceList;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PriceListExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private PriceList $priceList,
    ) {}

    public function title(): string
    {
        return $this->priceList->name;
    }

    public function collection()
    {
        return $this->priceList->items()
            ->with(['product:id,code,name,base_price', 'variant:id,name,sku', 'unit:id,name'])
            ->get();
    }

    public function headings(): array
    {
        return ['Mã HH', 'Tên hàng hóa', 'Biến thể', 'ĐVT', 'Giá gốc', 'Giá bảng giá'];
    }

    public function map($item): array
    {
        return [
            $item->product?->code,
            $item->product?->name,
            $item->variant?->name ?? '',
            $item->unit?->name ?? '',
            $item->product?->base_price,
            $item->price,
        ];
    }
}
