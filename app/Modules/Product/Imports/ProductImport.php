<?php

namespace App\Modules\Product\Imports;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductCategory;
use App\Modules\Product\Models\Brand;
use App\Modules\Product\Models\ProductUnit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToModel, WithHeadingRow
{
    protected int $count = 0;

    public function model(array $row)
    {
        // Tìm category, brand, unit theo tên
        $category = ! empty($row['nhom_hang'])
            ? ProductCategory::where('name', $row['nhom_hang'])->first()
            : null;

        $brand = ! empty($row['thuong_hieu'])
            ? Brand::where('name', $row['thuong_hieu'])->first()
            : null;

        $unit = ! empty($row['dvt'])
            ? ProductUnit::where('name', $row['dvt'])->first()
            : null;

        $type = match (mb_strtolower(trim($row['loai'] ?? ''))) {
            'dịch vụ', 'dich vu', 'service' => 'service',
            'combo' => 'combo',
            'sản xuất', 'san xuat', 'manufacturing' => 'manufacturing',
            default => 'product',
        };

        $this->count++;

        return new Product([
            'name' => $row['ten_hang'],
            'code' => $row['ma_hang'] ?? null,
            'barcode' => $row['ma_vach'] ?? null,
            'type' => $type,
            'category_id' => $category?->id,
            'brand_id' => $brand?->id,
            'base_unit_id' => $unit?->id,
            'base_price' => $row['gia_ban'] ?? 0,
            'cost_price' => $row['gia_von'] ?? 0,
            'weight' => $row['trong_luong'] ?? null,
            'point' => $row['diem'] ?? 0,
        ]);
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
