<?php

namespace Database\Seeders;

use App\Modules\Product\Models\Brand;
use App\Modules\Product\Models\Location;
use App\Modules\Product\Models\ProductAttribute;
use App\Modules\Product\Models\ProductCategory;
use App\Modules\Product\Models\ProductUnit;
use Illuminate\Database\Seeder;

class ProductModuleSeeder extends Seeder
{
    public function run(): void
    {
        // ── Nhóm hàng ──
        $categories = [
            ['name' => 'Thực phẩm', 'slug' => 'thuc-pham', 'children' => [
                ['name' => 'Bánh kẹo', 'slug' => 'banh-keo'],
                ['name' => 'Đồ uống', 'slug' => 'do-uong'],
                ['name' => 'Gia vị', 'slug' => 'gia-vi'],
            ]],
            ['name' => 'Đồ dùng gia đình', 'slug' => 'do-dung-gia-dinh', 'children' => [
                ['name' => 'Chăm sóc cá nhân', 'slug' => 'cham-soc-ca-nhan'],
                ['name' => 'Vệ sinh nhà cửa', 'slug' => 've-sinh-nha-cua'],
            ]],
            ['name' => 'Điện tử', 'slug' => 'dien-tu', 'children' => [
                ['name' => 'Phụ kiện điện thoại', 'slug' => 'phu-kien-dien-thoai'],
                ['name' => 'Thiết bị gia dụng', 'slug' => 'thiet-bi-gia-dung'],
            ]],
            ['name' => 'Thời trang', 'slug' => 'thoi-trang', 'children' => [
                ['name' => 'Áo', 'slug' => 'ao'],
                ['name' => 'Quần', 'slug' => 'quan'],
                ['name' => 'Phụ kiện', 'slug' => 'phu-kien'],
            ]],
        ];

        foreach ($categories as $catData) {
            $children = $catData['children'] ?? [];
            unset($catData['children']);
            $parent = ProductCategory::create($catData);

            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                ProductCategory::create($childData);
            }
        }

        // ── Thương hiệu ──
        $brands = ['Samsung', 'Apple', 'Unilever', 'P&G', 'Vinamilk', 'TH True Milk', 'Nike', 'Adidas'];
        foreach ($brands as $name) {
            Brand::create(['name' => $name]);
        }

        // ── Vị trí ──
        $locations = ['Kệ A1', 'Kệ A2', 'Kệ B1', 'Kệ B2', 'Tủ kính 1', 'Tủ kính 2', 'Kho chính'];
        foreach ($locations as $name) {
            Location::create(['name' => $name]);
        }

        // ── Đơn vị tính ──
        $units = ['Cái', 'Chai', 'Lon', 'Hộp', 'Thùng', 'Kg', 'Gói', 'Bộ', 'Đôi', 'Túi', 'Lọ', 'Bịch'];
        foreach ($units as $name) {
            ProductUnit::create(['name' => $name]);
        }

        // ── Thuộc tính ──
        $attributes = [
            'Size' => ['S', 'M', 'L', 'XL', 'XXL'],
            'Màu sắc' => ['Đỏ', 'Xanh', 'Đen', 'Trắng', 'Vàng'],
            'Chất liệu' => ['Cotton', 'Polyester', 'Lụa', 'Kaki'],
        ];

        foreach ($attributes as $attrName => $values) {
            $attr = ProductAttribute::create(['name' => $attrName]);
            foreach ($values as $i => $value) {
                $attr->values()->create(['value' => $value, 'sort_order' => $i]);
            }
        }
    }
}
