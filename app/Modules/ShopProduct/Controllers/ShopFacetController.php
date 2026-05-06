<?php

namespace App\Modules\ShopProduct\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShopProduct\Models\ShopEntry;
use App\Modules\ShopProduct\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Endpoint phụ trợ cho trang admin: derive Categories, Brands, Inventory từ bảng
 * shop_products đã được import. Module ShopAdmin cũ (có bảng riêng cho từng entity)
 * đã bị xoá khi reset DB; phiên bản tối thiểu này chỉ tổng hợp distinct values từ
 * cột text trên products để FE hiển thị filter & list.
 */
class ShopFacetController extends Controller
{
    public function categories(): JsonResponse
    {
        // Cache 60s — categories tree được tính từ scan + group full bảng shop_products
        // và build cây bằng PHP, mỗi request tốn vài chục ms; cache giúp navigation
        // trong /admin nhanh khi nhiều tab cùng gọi.
        $payload = Cache::remember('shop_facets:categories:v1', 60, function () {
            return $this->buildCategoriesPayload();
        });

        return response()->json($payload);
    }

    private function buildCategoriesPayload(): array
    {
        // Lấy distinct category + count sản phẩm trực tiếp ở từng path đầy đủ.
        $rawRows = ShopProduct::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->selectRaw('category, count(*) as cnt')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        // Tách path bằng các separator phổ biến: ">>", "/", ">", "->" (theo thứ tự ưu tiên).
        // Mỗi prefix path → 1 node trong cây. Cộng dồn count cho cả ancestor.
        $nodesByPath = [];   // 'a||b' => ['id', 'parentId', 'name', 'slug', 'fullPath', 'productCount']
        $idCounter = 1000000;

        foreach ($rawRows as $row) {
            $segments = $this->splitCategoryPath((string) $row->category);
            if (empty($segments)) {
                continue;
            }

            // Đảm bảo mỗi prefix segment đều có node trong cây.
            $accumulated = [];
            $parentKey = null;
            foreach ($segments as $segment) {
                $accumulated[] = $segment;
                $key = implode('||', $accumulated);
                if (! isset($nodesByPath[$key])) {
                    $nodesByPath[$key] = [
                        '_key' => $key,
                        '_parentKey' => $parentKey,
                        'id' => $idCounter++,
                        'parentId' => null, // sẽ resolve sau pass 2
                        'name' => $segment,
                        'slug' => Str::slug($segment).'-'.substr(md5($key), 0, 6),
                        'fullPath' => implode(' >> ', $accumulated),
                        'depth' => \count($accumulated) - 1,
                        'productCount' => 0,
                        'active' => true,
                        'showOnMenu' => true,
                        'icon' => 'ri-folder-line',
                        'description' => null,
                    ];
                }
                $parentKey = $key;
            }

            // Cộng dồn count cho path đầy đủ + tất cả ancestor.
            $accumulated = [];
            foreach ($segments as $segment) {
                $accumulated[] = $segment;
                $key = implode('||', $accumulated);
                $nodesByPath[$key]['productCount'] += (int) $row->cnt;
            }
        }

        // Resolve parentId từ _parentKey.
        foreach ($nodesByPath as &$node) {
            if ($node['_parentKey'] !== null && isset($nodesByPath[$node['_parentKey']])) {
                $node['parentId'] = $nodesByPath[$node['_parentKey']]['id'];
            }
        }
        unset($node);

        // Sắp xếp theo depth rồi alphabet để FE hiển thị tree gọn.
        $list = array_values($nodesByPath);
        usort($list, function ($a, $b) {
            return [$a['depth'], $a['fullPath']] <=> [$b['depth'], $b['fullPath']];
        });

        // Bỏ key nội bộ trước khi trả về.
        $data = array_map(static function ($n) {
            unset($n['_key'], $n['_parentKey']);

            return $n;
        }, $list);

        $existingKeys = [];
        foreach ($data as $item) {
            $existingKeys[strtolower((string) ($item['parentId'] ?? '')).'|'.mb_strtolower((string) $item['name'])] = true;
        }
        foreach (ShopEntry::where('entity', 'categories')->orderBy('id')->get() as $entry) {
            $item = $this->serializeCategory($entry);
            $key = strtolower((string) ($item['parentId'] ?? '')).'|'.mb_strtolower((string) $item['name']);
            if (! isset($existingKeys[$key])) {
                $data[] = $item;
                $existingKeys[$key] = true;
            }
        }

        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => \count($data),
                'perPage' => \count($data),
                'currentPage' => 1,
                'lastPage' => 1,
            ],
        ];
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $entry = ShopEntry::create([
            'entity' => 'categories',
            'data' => $this->normalizeCategoryData($request->all()),
            'search_text' => $this->searchText($request->all()),
        ]);
        $this->invalidateFacetCache();

        return $this->success($this->serializeCategory($entry), 'Đã tạo danh mục', 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $entry = ShopEntry::where('entity', 'categories')->find($id);
        if (! $entry) {
            if ($id < 1000000) {
                return $this->notFound('Không tìm thấy danh mục');
            }

            $entry = ShopEntry::create([
                'entity' => 'categories',
                'data' => $this->normalizeCategoryData($request->all()),
                'search_text' => $this->searchText($request->all()),
            ]);
            $this->invalidateFacetCache();

            return $this->success($this->serializeCategory($entry), 'Đã lưu danh mục');
        }

        $data = array_merge((array) $entry->data, $this->normalizeCategoryData($request->all()));
        $entry->update([
            'data' => $data,
            'search_text' => $this->searchText($data),
        ]);
        $this->invalidateFacetCache();

        return $this->success($this->serializeCategory($entry->fresh()), 'Đã cập nhật danh mục');
    }

    public function destroyCategory(int $id): JsonResponse
    {
        $entry = ShopEntry::where('entity', 'categories')->find($id);
        if ($entry) {
            $entry->delete();
            $this->invalidateFacetCache();
        }

        return $this->success(['deleted' => $entry ? 1 : 0], 'Đã xóa danh mục');
    }

    /** Tách "Bánh Kẹo, Sữa>>Bánh>>Mochi" → ['Bánh Kẹo, Sữa', 'Bánh', 'Mochi']. */
    private function splitCategoryPath(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        // Thử các separator theo thứ tự — chọn separator tách được nhiều nhất.
        $candidates = ['>>', '->', ' / ', '>', '/'];
        $bestParts = [$raw];
        foreach ($candidates as $sep) {
            $parts = array_values(array_filter(array_map('trim', explode($sep, $raw)), static fn ($v) => $v !== ''));
            if (\count($parts) > \count($bestParts)) {
                $bestParts = $parts;
            }
        }

        return $bestParts;
    }

    public function brands(): JsonResponse
    {
        $payload = Cache::remember('shop_facets:brands:v1', 60, function () {
            $rows = ShopProduct::query()
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->select('brand')
                ->groupBy('brand')
                ->orderBy('brand')
                ->pluck('brand')
                ->values();

            $data = $rows->map(function (string $name, int $idx) {
                $slug = Str::slug($name);

                return [
                    'id' => 1000000 + $idx,
                    'name' => $name,
                    'slug' => $slug,
                    'logoSource' => null,
                    'logo' => null,
                    'link' => '/brands/'.$slug,
                    'active' => true,
                    'sortOrder' => $idx,
                    'createdAt' => null,
                    'updatedAt' => null,
                ];
            })->all();

            $existing = [];
            foreach ($data as $item) {
                $existing[mb_strtolower((string) $item['name'])] = true;
            }
            foreach (ShopEntry::where('entity', 'brands')->orderBy('id')->get() as $entry) {
                $item = $this->serializeBrand($entry);
                $key = mb_strtolower((string) $item['name']);
                if (! isset($existing[$key])) {
                    $data[] = $item;
                    $existing[$key] = true;
                }
            }

            return [
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => \count($data),
                    'perPage' => \count($data),
                    'currentPage' => 1,
                    'lastPage' => 1,
                ],
            ];
        });

        return response()->json($payload);
    }

    public function storeBrand(Request $request): JsonResponse
    {
        $entry = ShopEntry::create([
            'entity' => 'brands',
            'data' => $this->normalizeBrandData($request->all()),
            'search_text' => $this->searchText($request->all()),
        ]);
        $this->invalidateFacetCache();

        return $this->success($this->serializeBrand($entry), 'Đã tạo thương hiệu', 201);
    }

    public function updateBrand(Request $request, int $id): JsonResponse
    {
        $entry = ShopEntry::where('entity', 'brands')->find($id);
        if (! $entry) {
            if ($id < 1000000) {
                return $this->notFound('Không tìm thấy thương hiệu');
            }

            $entry = ShopEntry::create([
                'entity' => 'brands',
                'data' => $this->normalizeBrandData($request->all()),
                'search_text' => $this->searchText($request->all()),
            ]);
            $this->invalidateFacetCache();

            return $this->success($this->serializeBrand($entry), 'Đã lưu thương hiệu');
        }

        $data = array_merge((array) $entry->data, $this->normalizeBrandData($request->all()));
        $entry->update([
            'data' => $data,
            'search_text' => $this->searchText($data),
        ]);
        $this->invalidateFacetCache();

        return $this->success($this->serializeBrand($entry->fresh()), 'Đã cập nhật thương hiệu');
    }

    public function destroyBrand(int $id): JsonResponse
    {
        $entry = ShopEntry::where('entity', 'brands')->find($id);
        if ($entry) {
            $entry->delete();
            $this->invalidateFacetCache();
        }

        return $this->success(['deleted' => $entry ? 1 : 0], 'Đã xóa thương hiệu');
    }

    public function inventoryHistory(): JsonResponse
    {
        // Module ShopAdmin gốc có bảng shop_inventory_movements; phiên bản tối thiểu
        // không track lịch sử nên trả về rỗng. Khi user cần history, sẽ thêm sau.
        return response()->json([
            'success' => true,
            'data' => [],
            'meta' => [
                'total' => 0,
                'perPage' => 0,
                'currentPage' => 1,
                'lastPage' => 1,
            ],
        ]);
    }

    public function inventoryStats(): JsonResponse
    {
        $payload = Cache::remember('shop_inventory:stats:v1', 30, function () {
            $row = ShopProduct::query()
                ->selectRaw('count(*) as total_products')
                ->selectRaw('coalesce(sum(stock), 0) as total_stock')
                ->selectRaw('coalesce(sum(reserved), 0) as total_reserved')
                ->selectRaw('count(*) filter (where stock <= threshold) as low_stock_count')
                ->selectRaw('coalesce(sum(stock * cost), 0) as total_value')
                ->first();

            return [
                'success' => true,
                'data' => [
                    'totalProducts' => (int) ($row->total_products ?? 0),
                    'totalStock' => (int) ($row->total_stock ?? 0),
                    'totalReserved' => (int) ($row->total_reserved ?? 0),
                    'lowStockCount' => (int) ($row->low_stock_count ?? 0),
                    'totalValue' => (float) ($row->total_value ?? 0),
                ],
            ];
        });

        return response()->json($payload);
    }

    private function serializeCategory(ShopEntry $entry): array
    {
        $data = (array) $entry->data;
        $name = (string) ($data['name'] ?? '');

        return [
            'id' => (int) $entry->id,
            'parentId' => $data['parent_id'] ?? $data['parentId'] ?? null,
            'name' => $name,
            'slug' => (string) ($data['slug'] ?? Str::slug($name)),
            'active' => (bool) ($data['active'] ?? true),
            'showOnMenu' => (bool) ($data['show_on_menu'] ?? $data['showOnMenu'] ?? true),
            'icon' => (string) ($data['icon'] ?? 'ri-folder-line'),
            'description' => $data['description'] ?? null,
            'productCount' => 0,
            'createdAt' => $entry->created_at?->toIso8601String(),
            'updatedAt' => $entry->updated_at?->toIso8601String(),
        ];
    }

    private function normalizeCategoryData(array $raw): array
    {
        $name = trim((string) ($raw['name'] ?? ''));

        return [
            'parent_id' => $raw['parent_id'] ?? $raw['parentId'] ?? null,
            'name' => $name,
            'slug' => (string) ($raw['slug'] ?? Str::slug($name)),
            'icon' => (string) ($raw['icon'] ?? 'ri-folder-line'),
            'description' => $raw['description'] ?? null,
            'show_on_menu' => (bool) ($raw['show_on_menu'] ?? $raw['showOnMenu'] ?? true),
            'active' => (bool) ($raw['active'] ?? true),
        ];
    }

    private function serializeBrand(ShopEntry $entry): array
    {
        $data = (array) $entry->data;
        $name = (string) ($data['name'] ?? '');
        $slug = (string) ($data['slug'] ?? Str::slug($name));

        return [
            'id' => (int) $entry->id,
            'name' => $name,
            'slug' => $slug,
            'logoSource' => $data['logo'] ?? $data['logoSource'] ?? null,
            'logo' => $data['logo'] ?? null,
            'link' => (string) ($data['link'] ?? '/products?brand='.urlencode($name)),
            'active' => (bool) ($data['active'] ?? true),
            'sortOrder' => (int) ($data['sort_order'] ?? $data['sortOrder'] ?? 0),
            'createdAt' => $entry->created_at?->toIso8601String(),
            'updatedAt' => $entry->updated_at?->toIso8601String(),
        ];
    }

    private function normalizeBrandData(array $raw): array
    {
        $name = trim((string) ($raw['name'] ?? ''));

        return [
            'name' => $name,
            'slug' => (string) ($raw['slug'] ?? Str::slug($name)),
            'logo' => $raw['logo'] ?? $raw['logoSource'] ?? null,
            'link' => (string) ($raw['link'] ?? '/products?brand='.urlencode($name)),
            'active' => (bool) ($raw['active'] ?? true),
            'sort_order' => (int) ($raw['sort_order'] ?? $raw['sortOrder'] ?? 0),
        ];
    }

    private function searchText(array $data): string
    {
        return mb_substr(implode(' ', array_map(static fn ($v) => is_scalar($v) ? (string) $v : '', $data)), 0, 4000);
    }

    private function invalidateFacetCache(): void
    {
        Cache::forget('shop_facets:categories:v1');
        Cache::forget('shop_facets:brands:v1');
    }
}
