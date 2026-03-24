<?php

namespace App\Modules\Product\Exports;

use App\Modules\Product\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection()
    {
        $query = Product::with(['category:id,name', 'brand:id,name', 'baseUnit:id,name'])
            ->filter($this->filters);

        // Hỗ trợ xuất theo ids chọn lọc
        if (! empty($this->filters['ids'])) {
            $query->whereIn('id', $this->filters['ids']);
        }

        $products = $query->get();

        return $products->map(fn ($p) => [
            'id' => $p->id,
            'code' => $p->code,
            'barcode' => $p->barcode,
            'name' => $p->name,
            'type' => $p->type,
            'category' => $p->category?->name ?? '',
            'brand' => $p->brand?->name ?? '',
            'base_unit' => $p->baseUnit?->name ?? '',
            'base_price' => (float) $p->base_price,
            'cost_price' => (float) $p->cost_price,
            'weight' => $p->weight,
            'status' => $p->status,
            'is_active' => $p->is_active ? 'Có' : 'Không',
            'point' => $p->point,
            'created_at' => $p->created_at?->format('d/m/Y H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return [
            'ID', 'Mã hàng', 'Mã vạch', 'Tên hàng', 'Loại',
            'Nhóm hàng', 'Thương hiệu', 'ĐVT', 'Giá bán', 'Giá vốn',
            'Trọng lượng (g)', 'Trạng thái', 'Kinh doanh', 'Điểm', 'Ngày tạo',
        ];
    }
}
