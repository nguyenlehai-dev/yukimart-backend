<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\Brand;
use Illuminate\Support\Facades\DB;

class BrandService
{
    public function list(array $filters, int $perPage = 20)
    {
        return Brand::filter($filters)->paginate($perPage);
    }

    public function options()
    {
        return Brand::where('status', 'active')->orderBy('name')->get(['id', 'name']);
    }

    public function find(int $id): Brand
    {
        return Brand::findOrFail($id);
    }

    public function store(array $data): Brand
    {
        return DB::transaction(fn () => Brand::create($data));
    }

    public function update(Brand $brand, array $data): Brand
    {
        return DB::transaction(function () use ($brand, $data) {
            $brand->update($data);

            return $brand->fresh();
        });
    }

    public function destroy(Brand $brand): bool
    {
        if ($brand->products()->exists()) {
            throw new \Exception('Không thể xóa thương hiệu đang có sản phẩm.');
        }

        return DB::transaction(fn () => $brand->delete());
    }
}
