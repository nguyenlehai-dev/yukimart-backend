<?php

namespace App\Modules\Post\Services;

use App\Modules\Core\Enums\StatusEnum;
use App\Modules\Post\Exports\PostCategoriesExport;
use App\Modules\Post\Imports\PostCategoriesImport;
use App\Modules\Post\Models\PostCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PostCategoryService
{
    public function publicList(array $filters = []): Collection
    {
        $publicFilters = [
            ...$filters,
            'status' => StatusEnum::Active->value,
            'sort_by' => 'sort_order',
            'sort_order' => 'asc',
        ];

        return $this->getFlatTreeOrdered($publicFilters);
    }

    public function publicOptions(array $filters = []): Collection
    {
        $publicFilters = [
            ...$filters,
            'status' => StatusEnum::Active->value,
            'sort_by' => 'sort_order',
            'sort_order' => 'asc',
        ];

        return PostCategory::query()
            ->select(['id', 'name', 'description'])
            ->filter($publicFilters)
            ->treeOrder()
            ->get();
    }

    public function stats(array $filters): array
    {
        $base = PostCategory::filter($filters);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', StatusEnum::Active->value)->count(),
            'inactive' => (clone $base)->where('status', '!=', StatusEnum::Active->value)->count(),
        ];
    }

    public function index(array $filters, int $limit)
    {
        return PostCategory::with(['creator', 'editor', 'parent'])
            ->filter($filters)
            ->treeOrder()
            ->paginate($limit);
    }

    public function tree(?string $status)
    {
        $query = PostCategory::query()
            ->when($status, fn ($q, $value) => $q->where('status', $value));
        $items = $query->orderBy('sort_order')->orderBy('id')->get();

        return $this->buildTree($items);
    }

    public function show(PostCategory $category): PostCategory
    {
        return $category->load(['creator', 'editor', 'parent', 'children' => fn ($q) => $q->orderBy('sort_order')]);
    }

    public function store(array $data): PostCategory
    {
        $parentId = $data['parent_id'] ?? null;

        if ($parentId) {
            PostCategory::findOrFail($parentId);
        }

        return PostCategory::create($data);
    }

    public function update(PostCategory $category, array $data): array
    {
        $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : null;

        if ($parentId !== null && (int) $parentId !== 0) {
            $parent = PostCategory::findOrFail($parentId);

            if ($this->isDescendantOf($parent->id, $category->id)) {
                return [
                    'ok' => false,
                    'message' => 'Không thể chọn danh mục con làm danh mục cha.',
                    'code' => 422,
                    'error_code' => 'CONFLICT',
                ];
            }
        }

        if ($parentId !== null && (int) $parentId === 0) {
            $data['parent_id'] = null;
        }

        $category->update($data);

        return [
            'ok' => true,
            'category' => $category->fresh(['parent', 'children']),
        ];
    }

    public function destroy(PostCategory $category): void
    {
        $category->delete();
    }

    public function bulkDestroy(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            PostCategory::whereIn('id', $ids)->get()->each->delete();
        });
    }

    public function bulkUpdateStatus(array $ids, string $status): void
    {
        PostCategory::whereIn('id', $ids)->update(['status' => $status]);
    }

    public function export(array $filters): BinaryFileResponse
    {
        return Excel::download(new PostCategoriesExport($filters), 'post-categories.xlsx');
    }

    public function import($file): void
    {
        Excel::import(new PostCategoriesImport, $file);
    }

    public function changeStatus(PostCategory $category, string $status): PostCategory
    {
        $category->update(['status' => $status]);

        return $category->load(['parent', 'children']);
    }

    public function getFlatTreeOrdered(array $filters = []): Collection
    {
        $all = PostCategory::with(['creator', 'editor'])->filter($filters)->get();
        $tree = $this->buildTree($all);
        $result = collect();
        $flatten = function ($nodes) use (&$flatten, &$result) {
            foreach ($nodes as $node) {
                $result->push($node);
                $flatten($node->children);
            }
        };
        $flatten($tree);

        return $result;
    }

    public function getDepth(PostCategory $category): int
    {
        $depth = 0;
        $parentId = $category->parent_id;
        $ids = [$category->id];

        while ($parentId) {
            if (in_array($parentId, $ids)) {
                break;
            }

            $ids[] = $parentId;
            $parent = PostCategory::find($parentId);
            $parentId = $parent ? $parent->parent_id : null;
            $depth++;
        }

        return $depth;
    }

    public function generateUniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $query = PostCategory::where('slug', $slug);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $index = 0;
        while ($query->exists()) {
            $slug = $base.'-'.(++$index);
            $query = PostCategory::where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    public function buildTree(Collection $items): Collection
    {
        $grouped = $items->groupBy('parent_id');
        $builder = function ($parentId) use ($grouped, &$builder) {
            return ($grouped->get($parentId) ?? collect())
                ->map(function ($node) use (&$builder) {
                    $node->setRelation('children', $builder($node->id));

                    return $node;
                })
                ->values();
        };

        return $builder(null);
    }

    private function isDescendantOf(int $candidateId, int $id): bool
    {
        $current = PostCategory::find($id);

        while ($current && $current->parent_id) {
            if ($current->parent_id === $candidateId) {
                return true;
            }

            $current = PostCategory::find($current->parent_id);
        }

        return false;
    }
}
