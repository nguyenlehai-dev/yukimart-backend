<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

class ProductCategoryService
{
    public function list(array $filters, int $perPage = 20)
    {
        return ProductCategory::filter($filters)->paginate($perPage);
    }

    public function tree()
    {
        $categories = ProductCategory::where('status', 'active')
            ->treeOrder()
            ->get();

        return $this->buildTree($categories);
    }

    public function options()
    {
        return ProductCategory::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    public function find(int $id): ProductCategory
    {
        return ProductCategory::findOrFail($id);
    }

    public function store(array $data): ProductCategory
    {
        return DB::transaction(fn () => ProductCategory::create($data));
    }

    public function update(ProductCategory $category, array $data): ProductCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update($data);

            return $category->fresh();
        });
    }

    public function destroy(ProductCategory $category): bool
    {
        if ($category->products()->exists()) {
            throw new \Exception('Không thể xóa nhóm hàng đang chứa sản phẩm.');
        }
        if ($category->children()->exists()) {
            throw new \Exception('Không thể xóa nhóm hàng đang chứa nhóm con.');
        }

        return DB::transaction(fn () => $category->delete());
    }

    protected function buildTree($categories, $parentId = null): array
    {
        $tree = [];
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                $node = $category->toArray();
                $node['children'] = $children;
                $tree[] = $node;
            }
        }

        return $tree;
    }
}
