<?php

namespace App\Modules\ShopProduct\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShopProduct\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
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
        $idCounter = 1;

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
                'id' => $idx + 1,
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

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => \count($data),
                'perPage' => \count($data),
                'currentPage' => 1,
                'lastPage' => 1,
            ],
        ]);
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
        $row = ShopProduct::query()
            ->selectRaw('count(*) as total_products')
            ->selectRaw('coalesce(sum(stock), 0) as total_stock')
            ->selectRaw('coalesce(sum(reserved), 0) as total_reserved')
            ->selectRaw("count(*) filter (where stock <= threshold) as low_stock_count")
            ->selectRaw("coalesce(sum(stock * cost), 0) as total_value")
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'totalProducts' => (int) ($row->total_products ?? 0),
                'totalStock' => (int) ($row->total_stock ?? 0),
                'totalReserved' => (int) ($row->total_reserved ?? 0),
                'lowStockCount' => (int) ($row->low_stock_count ?? 0),
                'totalValue' => (float) ($row->total_value ?? 0),
            ],
        ]);
    }
}
