<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\ProductUnit;
use Illuminate\Support\Facades\DB;

class ProductUnitService
{
    public function list(int $perPage = 50)
    {
        return ProductUnit::orderBy('name')->paginate($perPage);
    }

    public function options()
    {
        return ProductUnit::orderBy('name')->get(['id', 'name']);
    }

    public function store(array $data): ProductUnit
    {
        return DB::transaction(fn () => ProductUnit::create($data));
    }

    public function update(ProductUnit $unit, array $data): ProductUnit
    {
        return DB::transaction(function () use ($unit, $data) {
            $unit->update($data);

            return $unit->fresh();
        });
    }

    public function destroy(ProductUnit $unit): bool
    {
        return DB::transaction(fn () => $unit->delete());
    }
}
