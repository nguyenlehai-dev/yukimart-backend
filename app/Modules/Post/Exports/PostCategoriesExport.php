<?php

namespace App\Modules\Post\Exports;

use App\Modules\Post\Models\PostCategory;
use App\Modules\Post\Services\PostCategoryService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PostCategoriesExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * Xuất danh mục theo bộ lọc, thứ tự cây (cha trước con) để import lại đúng cấu trúc parent_id.
     */
    public function collection()
    {
        $service = app(PostCategoryService::class);
        $nodes = $service->getFlatTreeOrdered($this->filters);

        return $nodes->map(function (PostCategory $category) use ($service) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'status' => $category->status,
                'sort_order' => $category->sort_order,
                'parent_id' => $category->parent_id,
                'parent_slug' => $category->parent_id ? (PostCategory::find($category->parent_id)?->slug ?? '') : '',
                'depth' => $service->getDepth($category),
                'created_by' => $category->creator?->name ?? 'N/A',
                'updated_by' => $category->editor?->name ?? 'N/A',
                'created_at' => $category->created_at?->format('d/m/Y H:i:s'),
                'updated_at' => $category->updated_at?->format('d/m/Y H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID', 'Name', 'Slug', 'Description', 'Status', 'Sort Order',
            'Parent ID', 'Parent Slug', 'Depth',
            'Created By', 'Updated By', 'Created At', 'Updated At',
        ];
    }
}
