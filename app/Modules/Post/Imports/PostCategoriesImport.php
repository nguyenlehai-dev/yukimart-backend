<?php

namespace App\Modules\Post\Imports;

use App\Modules\Core\Enums\StatusEnum;
use App\Modules\Post\Models\PostCategory;
use App\Modules\Post\Services\PostCategoryService;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PostCategoriesImport implements ToModel, WithHeadingRow
{
    /**
     * Nhập danh mục theo thứ tự (dòng trước = cha, dòng sau = con khi cùng parent_slug).
     * Cột: name, slug, description, status, sort_order, parent_slug.
     */
    public function model(array $row)
    {
        $parentSlug = $row['parent_slug'] ?? $row['parent slug'] ?? '';
        $parent = $parentSlug
            ? PostCategory::where('slug', $parentSlug)->first()
            : null;

        $slug = $row['slug'] ?? Str::slug($row['name']);
        $slug = app(PostCategoryService::class)->generateUniqueSlug($slug);

        return new PostCategory([
            'name' => $row['name'] ?? '',
            'slug' => $slug,
            'description' => $row['description'] ?? null,
            'status' => $row['status'] ?? StatusEnum::Active->value,
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'parent_id' => $parent?->id,
        ]);
    }
}
