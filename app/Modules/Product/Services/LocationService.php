<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\Location;
use Illuminate\Support\Facades\DB;

class LocationService
{
    public function list(array $filters, int $perPage = 20)
    {
        return Location::filter($filters)->paginate($perPage);
    }

    public function options()
    {
        return Location::where('status', 'active')->orderBy('name')->get(['id', 'name']);
    }

    public function find(int $id): Location
    {
        return Location::findOrFail($id);
    }

    public function store(array $data): Location
    {
        return DB::transaction(fn () => Location::create($data));
    }

    public function update(Location $location, array $data): Location
    {
        return DB::transaction(function () use ($location, $data) {
            $location->update($data);

            return $location->fresh();
        });
    }

    public function destroy(Location $location): bool
    {
        if ($location->products()->exists()) {
            throw new \Exception('Không thể xóa vị trí đang có sản phẩm.');
        }

        return DB::transaction(fn () => $location->delete());
    }
}
