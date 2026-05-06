<?php

namespace App\Modules\ShopProduct\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShopProduct\Imports\ShopProductImport;
use App\Modules\ShopProduct\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller tối thiểu cho ShopProduct: phục vụ list/CRUD + import Excel.
 *
 * Module ShopAdmin gốc đã bị xóa khi reset DB. Đây là phiên bản gọn nhẹ chỉ giữ phần
 * cốt lõi giúp user import lại sản phẩm từ Excel. Các endpoint khác (categories, brands,
 * sections, ...) tạm thời dùng stub trong app/Modules/ShopStub.
 */
class ShopProductController extends Controller
{
    /** Trường schema chính, dùng cho mapping Excel và serialize response. */
    private const FIELDS = [
        ['key' => 'sku', 'label' => 'SKU', 'required' => true],
        ['key' => 'barcode', 'label' => 'Mã vạch'],
        ['key' => 'name', 'label' => 'Tên sản phẩm', 'required' => true],
        ['key' => 'product_type', 'label' => 'Loại'],
        ['key' => 'category_path', 'label' => 'Đường dẫn danh mục'],
        ['key' => 'category', 'label' => 'Danh mục'],
        ['key' => 'brand', 'label' => 'Thương hiệu'],
        ['key' => 'original_price', 'label' => 'Giá gốc'],
        ['key' => 'sale_price', 'label' => 'Giá lẻ'],
        ['key' => 'wholesale_price', 'label' => 'Giá sỉ'],
        ['key' => 'cost', 'label' => 'Giá vốn'],
        ['key' => 'stock', 'label' => 'Tồn'],
        ['key' => 'reserved', 'label' => 'Đã đặt'],
        ['key' => 'threshold', 'label' => 'Tồn tối thiểu'],
        ['key' => 'unit', 'label' => 'Đơn vị'],
        ['key' => 'status', 'label' => 'Trạng thái'],
        ['key' => 'description', 'label' => 'Mô tả'],
        ['key' => 'image_urls', 'label' => 'Ảnh (URL)'],
        ['key' => 'warehouse', 'label' => 'Kho'],
        ['key' => 'weight', 'label' => 'Khối lượng'],
        ['key' => 'points', 'label' => 'Điểm thưởng'],
    ];

    /**
     * Từ khóa nhận diện cột Excel cho mỗi field.
     *
     * Auto-detect chấm điểm theo thứ tự:
     *  - Khớp tuyệt đối với heading đã normalize → 100
     *  - Heading bắt đầu/kết thúc bằng keyword → 60
     *  - Heading chứa keyword như một token (sau khi tách dấu _) → 40
     *  - Heading chứa keyword như substring → 20
     * Field nào có column điểm cao nhất sẽ được map. Cùng điểm → giữ field đăng ký trước.
     */
    private const FIELD_KEYWORDS = [
        'sku' => ['sku', 'ma_sp', 'ma_san_pham', 'masp', 'ma_hang', 'mahang', 'product_code', 'code'],
        'barcode' => ['barcode', 'ma_vach', 'mavach', 'ean', 'upc'],
        'name' => ['name', 'ten', 'ten_sp', 'tensp', 'ten_san_pham', 'ten_hang', 'tenhang', 'product_name', 'product'],
        'product_type' => ['product_type', 'loai', 'loai_sp', 'loai_san_pham', 'type'],
        'category_path' => ['category_path', 'duong_dan_danh_muc', 'duong_dan'],
        'category' => ['category', 'danh_muc', 'danhmuc', 'nhom', 'nhom_hang'],
        'brand' => ['brand', 'thuong_hieu', 'thuonghieu', 'nha_san_xuat', 'hang_sx'],
        'original_price' => ['original_price', 'list_price', 'gia_goc', 'giagoc', 'gia_niem_yet', 'gianiemiet', 'gia_truoc_giam', 'giatruocgiam', 'gia_bia'],
        'sale_price' => ['sale_price', 'price', 'gia_ban_le', 'giabanle', 'gia_ban', 'giaban', 'gia_le', 'giale', 'don_gia', 'dongia', 'gia'],
        'wholesale_price' => ['wholesale_price', 'wholesale', 'gia_si', 'giasi', 'gia_ban_si', 'giabansi', 'gia_dai_ly', 'giadaily', 'dealer_price'],
        'cost' => ['cost', 'gia_von', 'giavon', 'gia_nhap', 'gianhap', 'cost_price'],
        'stock' => ['stock', 'ton_kho', 'tonkho', 'so_luong', 'soluong', 'qty', 'quantity', 'ton'],
        'reserved' => ['reserved', 'da_dat', 'dadat', 'so_luong_dat'],
        'threshold' => ['threshold', 'ton_toi_thieu', 'min_stock', 'min_qty', 'safety_stock', 'tonmin'],
        'unit' => ['unit', 'don_vi', 'donvi', 'dvt'],
        'status' => ['status', 'trang_thai', 'trangthai', 'tinh_trang'],
        'description' => ['description', 'mo_ta', 'mota', 'ghi_chu', 'note'],
        'image_urls' => ['image_urls', 'image', 'images', 'anh', 'hinh', 'hinh_anh', 'hinhanh', 'photo', 'picture'],
        'warehouse' => ['warehouse', 'kho', 'kho_hang', 'khohang'],
        'weight' => ['weight', 'khoi_luong', 'khoiluong', 'trong_luong', 'trongluong'],
        'points' => ['points', 'point', 'diem_thuong', 'diemthuong', 'reward_points'],
    ];

    /**
     * Danh sách sản phẩm. Hỗ trợ ?q, ?status, ?category/?cat, ?brand, ?min_price,
     * ?max_price, ?deal=hot, ?sort, ?page, ?per_page, ?all, ?limit.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShopProduct::query();

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'ilike', "%{$q}%")
                    ->orWhere('sku', 'ilike', "%{$q}%")
                    ->orWhere('barcode', 'ilike', "%{$q}%");
            });
        }
        if (($status = $request->query('status')) && $status !== 'all') {
            $query->where('status', $status);
        }
        // Hỗ trợ cả 'category' lẫn 'cat' (FE customerMenu link dùng 'cat'). Match
        // ILIKE để khớp cả full path "A>>B>>C" khi user truyền tên cha hoặc con.
        $category = $request->query('category') ?? $request->query('cat');
        if ($category && $category !== 'all') {
            $query->where('category', 'ilike', '%'.$category.'%');
        }
        if (($brand = $request->query('brand')) && $brand !== 'all') {
            $brands = array_values(array_filter(array_map('trim', explode(',', (string) $brand))));
            if ($brands !== []) {
                $query->where(function ($w) use ($brands) {
                    foreach ($brands as $item) {
                        $w->orWhere('brand', 'ilike', '%'.$item.'%');
                    }
                });
            }
        }
        if ($request->filled('min_price')) {
            $query->where('sale_price', '>=', max(0, (float) $request->query('min_price')));
        }
        if ($request->filled('max_price')) {
            $query->where('sale_price', '<=', max(0, (float) $request->query('max_price')));
        }
        if ($request->query('deal') === 'hot') {
            $query->where('is_hot_deal', true);
        }

        match ((string) $request->query('sort', 'newest')) {
            'price:asc', 'price_asc' => $query->orderBy('sale_price')->orderByDesc('id'),
            'price:desc', 'price_desc' => $query->orderByDesc('sale_price')->orderByDesc('id'),
            'name:asc', 'name_asc' => $query->orderBy('name')->orderByDesc('id'),
            'name:desc', 'name_desc' => $query->orderByDesc('name')->orderByDesc('id'),
            'created_at:asc' => $query->orderBy('created_at')->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        // FE truyền paginate=1 cho admin list, all=1/limit=N cho fetchAll/fetchPublicHome.
        // ?all=1 → cap 2000 (slim payload). ?limit=N → tôn trọng N (cap 2000).
        if ($request->boolean('all') || $request->has('limit')) {
            $requested = (int) ($request->query('limit') ?? 2000);
            $cap = max(1, min($requested, 2000));
            $items = $query->limit($cap)->get();
            $stats = $this->buildStats();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn ($p) => $this->serializeLite($p))->all(),
                'meta' => [
                    'total' => $items->count(),
                    'perPage' => $items->count(),
                    'currentPage' => 1,
                    'lastPage' => 1,
                ],
                'stats' => $stats,
                'facets' => $this->buildFacets(),
            ]);
        }

        $perPage = (int) ($request->query('per_page') ?? 20);
        $perPage = max(1, min($perPage, 200));
        $paginator = $query->paginate($perPage);
        $stats = $this->buildStats();

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn ($p) => $this->serialize($p))->all(),
            'meta' => [
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
            ],
            'stats' => $stats,
            'facets' => $this->buildFacets(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = ShopProduct::find($id);
        if (! $product) {
            return $this->notFound('Không tìm thấy sản phẩm');
        }

        return $this->success($this->serialize($product));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->normalizePricing($request->validate($this->validationRules()));
        $data['sku'] = $data['sku'] ?? $this->generateSku();
        $data['slug'] = Str::slug($data['name']).'-'.uniqid();
        $product = ShopProduct::create($data);
        $this->invalidateFacetCache();

        return $this->success($this->serialize($product), 'Đã tạo sản phẩm', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = ShopProduct::find($id);
        if (! $product) {
            return $this->notFound('Không tìm thấy sản phẩm');
        }
        $data = $this->normalizePricing($request->validate($this->validationRules($id)), $product);
        $product->update($data);
        $this->invalidateFacetCache();

        return $this->success($this->serialize($product->fresh()), 'Đã cập nhật');
    }

    public function destroy(int $id): JsonResponse
    {
        $product = ShopProduct::find($id);
        if (! $product) {
            return $this->notFound('Không tìm thấy sản phẩm');
        }
        $product->delete();
        $this->invalidateFacetCache();

        return $this->success(null, 'Đã xóa');
    }

    public function adjustStock(Request $request, int $id): JsonResponse
    {
        $product = ShopProduct::find($id);
        if (! $product) {
            return $this->notFound('Không tìm thấy sản phẩm');
        }
        $delta = (int) $request->input('delta', 0);
        $note = (string) ($request->input('note') ?? $request->input('reason') ?? '');
        $product->stock = max(0, $product->stock + $delta);
        $product->save();
        $this->invalidateFacetCache();

        return $this->success($this->serialize($product), $note !== '' ? $note : 'Đã điều chỉnh tồn');
    }

    /**
     * Đọc file Excel/CSV, trả về columns + rows mẫu + mapping mặc định cho FE.
     */
    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $reader = new ShopProductImport;
        try {
            Excel::import($reader, $request->file('file'));
        } catch (\Throwable $e) {
            return $this->error('Không đọc được file. '.$e->getMessage(), 422);
        }

        $columns = $reader->headings;
        $rowsSample = array_slice($reader->rows, 0, 20);

        $mapping = $this->autoDetectMapping($columns);

        // Clean giá trị từng ô để FE không bị NaN khi parseFloat trên chuỗi "70.000 ₫".
        // Phát hiện cột số dựa theo mapping (sale_price/cost/stock/...) → đổi sang số sạch.
        $numericFields = ['original_price', 'sale_price', 'wholesale_price', 'cost', 'weight'];
        $integerFields = ['stock', 'reserved', 'threshold', 'points'];
        $numericColumns = [];
        $integerColumns = [];
        foreach ($mapping as $field => $col) {
            if (\in_array($field, $numericFields, true)) {
                $numericColumns[] = $col;
            }
            if (\in_array($field, $integerFields, true)) {
                $integerColumns[] = $col;
            }
        }
        foreach ($rowsSample as $idx => $row) {
            foreach ($numericColumns as $col) {
                if (\array_key_exists($col, $row)) {
                    $rowsSample[$idx][$col] = $this->toFloat($row[$col]);
                }
            }
            foreach ($integerColumns as $col) {
                if (\array_key_exists($col, $row)) {
                    $rowsSample[$idx][$col] = $this->toInt($row[$col]);
                }
            }
        }

        return $this->success([
            'columns' => $columns,
            'rows' => $rowsSample,
            'rowCount' => \count($reader->rows),
            'mapping' => $mapping,
            'fields' => self::FIELDS,
        ]);
    }

    /**
     * Persist Excel rows → shop_products dùng mapping do FE gửi lên.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            'mapping' => ['required', 'string'],
        ]);

        $mapping = json_decode((string) $request->input('mapping'), true);
        if (! is_array($mapping) || empty($mapping['name'])) {
            return $this->error('Mapping không hợp lệ. Cần ít nhất cột "name".', 422);
        }
        $updateExisting = (bool) $request->input('update_existing', false);

        $reader = new ShopProductImport;
        try {
            Excel::import($reader, $request->file('file'));
        } catch (\Throwable $e) {
            return $this->error('Không đọc được file. '.$e->getMessage(), 422);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($reader->rows as $idx => $row) {
                $payload = $this->mapRow($row, $mapping);
                if (empty($payload['name'])) {
                    $skipped++;
                    if (count($errors) < 10) {
                        $errors[] = ['row' => $idx + 2, 'message' => 'Thiếu tên sản phẩm'];
                    }

                    continue;
                }

                $sku = $payload['sku'] ?? null;
                $existing = $sku ? ShopProduct::where('sku', $sku)->first() : null;
                $payload = $this->normalizePricing($payload, $existing);

                if ($existing) {
                    if ($updateExisting) {
                        $existing->update($payload);
                        $updated++;
                    } else {
                        $skipped++;
                    }

                    continue;
                }

                $payload['sku'] = $sku ?: $this->generateSku();
                $payload['slug'] = Str::slug($payload['name']).'-'.uniqid();
                ShopProduct::create($payload);
                $created++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->error('Lỗi khi nhập sản phẩm: '.$e->getMessage(), 500);
        }

        $this->invalidateFacetCache();

        return $this->success([
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ], 'Đã nhập sản phẩm');
    }

    private function invalidateFacetCache(): void
    {
        Cache::forget('shop_facets:categories:v1');
        Cache::forget('shop_facets:brands:v1');
        Cache::forget('shop_inventory:stats:v1');
    }

    /**
     * Export đơn giản — trả CSV. (Excel xlsx export cần thêm class, để sau khi user yêu cầu.)
     */
    public function export(): StreamedResponse
    {
        $filename = 'shop-products-'.date('Ymd').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            // BOM để Excel hiểu UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_column(self::FIELDS, 'key'));
            ShopProduct::query()->orderBy('id')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $p) {
                    $row = [];
                    foreach (self::FIELDS as $f) {
                        $row[] = (string) ($p->{$f['key']} ?? '');
                    }
                    fputcsv($out, $row);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ───────── helpers ─────────

    private function buildStats(): array
    {
        $rows = ShopProduct::query()
            ->selectRaw('count(*) as total')
            ->selectRaw("count(*) filter (where status = 'active') as active")
            ->selectRaw("count(*) filter (where status = 'draft') as draft")
            ->selectRaw("count(*) filter (where status = 'out_of_stock') as out_of_stock")
            ->selectRaw('count(*) filter (where stock > 0 and stock <= threshold) as low_stock')
            ->selectRaw('coalesce(sum(stock * cost), 0) as total_value')
            ->first();

        return [
            'total' => (int) ($rows->total ?? 0),
            'active' => (int) ($rows->active ?? 0),
            'draft' => (int) ($rows->draft ?? 0),
            'outOfStock' => (int) ($rows->out_of_stock ?? 0),
            'lowStock' => (int) ($rows->low_stock ?? 0),
            'totalValue' => (float) ($rows->total_value ?? 0),
        ];
    }

    private function buildFacets(): array
    {
        $brands = ShopProduct::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->selectRaw('brand, count(*) as count')
            ->groupBy('brand')
            ->orderBy('brand')
            ->get()
            ->map(fn ($row) => [
                'brand' => (string) $row->brand,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        return [
            'brands' => $brands,
        ];
    }

    /**
     * Slim payload cho danh sách lớn (?all=1) — bỏ các field nặng/dài (description,
     * imageUrls full string, slug, productType, etc.). Giữ lại các field FE cần để
     * hiển thị card + filter + thống kê.
     */
    private function serializeLite(ShopProduct $p): array
    {
        $images = $this->splitImages($p->image_urls);
        $pricing = $this->pricePayload($p);

        return [
            'id' => $p->id,
            'sku' => $p->sku,
            'barcode' => $p->barcode,
            'name' => $p->name,
            'category' => $p->category,
            'brand' => $p->brand,
            'price' => $pricing['salePrice'],
            'originalPrice' => $pricing['originalPrice'],
            'salePrice' => $pricing['salePrice'],
            'wholesalePrice' => $pricing['wholesalePrice'],
            'discount' => $pricing['discount'],
            'cost' => (float) $p->cost,
            'stock' => (int) $p->stock,
            'reserved' => (int) $p->reserved,
            'threshold' => (int) $p->threshold,
            'status' => $p->status,
            'image' => $images[0] ?? '',
            'isHotDeal' => (bool) $p->is_hot_deal,
            'isSuggested' => (bool) $p->is_suggested,
        ];
    }

    private function serialize(ShopProduct $p): array
    {
        $images = $this->splitImages($p->image_urls);
        $pricing = $this->pricePayload($p);

        return [
            'id' => $p->id,
            'sku' => $p->sku,
            'barcode' => $p->barcode,
            'name' => $p->name,
            'slug' => $p->slug,
            'productType' => $p->product_type,
            'categoryPath' => $p->category_path ? explode('/', $p->category_path) : [],
            'category' => $p->category,
            'brand' => $p->brand,
            'price' => $pricing['salePrice'],
            'originalPrice' => $pricing['originalPrice'],
            'salePrice' => $pricing['salePrice'],
            'wholesalePrice' => $pricing['wholesalePrice'],
            'discount' => $pricing['discount'],
            'cost' => (float) $p->cost,
            'stock' => (int) $p->stock,
            'reserved' => (int) $p->reserved,
            'threshold' => (int) $p->threshold,
            'unit' => $p->unit,
            'status' => $p->status,
            'description' => $p->description,
            'image' => $images[0] ?? '',
            'images' => $images,
            'imageUrls' => $p->image_urls,
            'isHotDeal' => (bool) $p->is_hot_deal,
            'isSuggested' => (bool) $p->is_suggested,
            'warehouse' => $p->warehouse,
            'weight' => $p->weight,
            'points' => $p->points,
            'createdAt' => $p->created_at?->toIso8601String(),
            'updatedAt' => $p->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Tách chuỗi ảnh nhiều URL (phân tách bằng ; , | xuống dòng) thành mảng URL/filename sạch.
     */
    private function splitImages(?string $raw): array
    {
        if ($raw === null) {
            return [];
        }
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        // Hỗ trợ JSON array (lưu cũ dạng JSON-encoded).
        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (\is_array($decoded)) {
                return array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $decoded), static fn ($v) => $v !== ''));
            }
        }
        $parts = preg_split('/[\s;,|]+/u', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn ($v) => $v !== ''));
    }

    private function validationRules(?int $ignoreId = null): array
    {
        $skuUnique = 'unique:shop_products,sku';
        if ($ignoreId) {
            $skuUnique .= ','.$ignoreId;
        }

        return [
            'sku' => ['nullable', 'string', 'max:64', $skuUnique],
            'barcode' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:64'],
            'category_path' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:128'],
            'brand' => ['nullable', 'string', 'max:128'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'reserved' => ['nullable', 'integer', 'min:0'],
            'threshold' => ['nullable', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'in:active,draft,out_of_stock'],
            'description' => ['nullable', 'string'],
            'image_urls' => ['nullable', 'string'],
            'warehouse' => ['nullable', 'string', 'max:64'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'points' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function mapRow(array $row, array $mapping): array
    {
        $out = [];
        foreach ($mapping as $field => $column) {
            if (! $column || ! \array_key_exists($column, $row)) {
                continue;
            }
            $value = $row[$column];
            if ($value === '' || $value === null) {
                continue;
            }

            $out[$field] = match ($field) {
                'original_price', 'sale_price', 'wholesale_price', 'cost', 'weight' => $this->toFloat($value),
                'stock', 'reserved', 'threshold', 'points' => $this->toInt($value),
                'status' => $this->toStatus($value),
                'image_urls' => implode(';', $this->splitImages(\is_string($value) ? $value : (string) $value)),
                default => \is_string($value) ? trim($value) : $value,
            };
        }

        return $out;
    }

    private function pricePayload(ShopProduct $p): array
    {
        $salePrice = (float) $p->sale_price;
        $originalPrice = (float) ($p->original_price ?: $salePrice);
        $wholesalePrice = (float) ($p->wholesale_price ?: $salePrice);
        $discount = $originalPrice > $salePrice && $salePrice > 0
            ? (int) round((1 - ($salePrice / $originalPrice)) * 100)
            : 0;

        return [
            'originalPrice' => $originalPrice,
            'salePrice' => $salePrice,
            'wholesalePrice' => $wholesalePrice,
            'discount' => max(0, $discount),
        ];
    }

    private function normalizePricing(array $data, ?ShopProduct $existing = null): array
    {
        $hasOriginal = array_key_exists('original_price', $data);
        $hasSale = array_key_exists('sale_price', $data);
        $hasWholesale = array_key_exists('wholesale_price', $data);

        if ($hasSale && (float) $data['sale_price'] <= 0 && $hasOriginal && (float) $data['original_price'] > 0) {
            $data['sale_price'] = (float) $data['original_price'];
        }

        if (! $hasSale && ! $existing) {
            $data['sale_price'] = $hasOriginal ? (float) $data['original_price'] : 0;
            $hasSale = true;
        }

        $salePrice = $hasSale ? (float) $data['sale_price'] : (float) ($existing?->sale_price ?? 0);

        if ($hasOriginal && (float) $data['original_price'] <= 0 && $salePrice > 0) {
            $data['original_price'] = $salePrice;
        }
        if ($hasWholesale && (float) $data['wholesale_price'] <= 0 && $salePrice > 0) {
            $data['wholesale_price'] = $salePrice;
        }

        if (! $hasOriginal && ! $existing) {
            $data['original_price'] = $salePrice;
        }
        if (! $hasWholesale && ! $existing) {
            $data['wholesale_price'] = $salePrice;
        }

        if ($existing && $hasSale) {
            if (! $hasOriginal && (float) ($existing->original_price ?? 0) <= 0) {
                $data['original_price'] = $salePrice;
            }
            if (! $hasWholesale && (float) ($existing->wholesale_price ?? 0) <= 0) {
                $data['wholesale_price'] = $salePrice;
            }
        }

        return $data;
    }

    private function toFloat(mixed $v): float
    {
        if (is_numeric($v)) {
            return (float) $v;
        }
        $cleaned = preg_replace('/[^\d.,-]/', '', (string) $v) ?? '';
        $cleaned = str_replace(',', '.', $cleaned);

        return (float) $cleaned;
    }

    private function toInt(mixed $v): int
    {
        if (is_numeric($v)) {
            return (int) $v;
        }
        $cleaned = preg_replace('/[^\d-]/', '', (string) $v) ?? '';

        return (int) $cleaned;
    }

    private function toStatus(mixed $v): string
    {
        $s = strtolower(trim((string) $v));
        if (in_array($s, ['active', 'on', '1', 'true', 'hoat_dong', 'hoatdong', 'dang_ban'], true)) {
            return 'active';
        }
        if (in_array($s, ['out_of_stock', 'het_hang', 'hethang', '0_stock', 'oos'], true)) {
            return 'out_of_stock';
        }

        return 'draft';
    }

    /**
     * Tự động nhận diện cột Excel cho từng field theo điểm số.
     */
    private function autoDetectMapping(array $columns): array
    {
        // Chuẩn bị normalized version của từng column (1 lần).
        $normalizedColumns = [];
        foreach ($columns as $col) {
            $normalizedColumns[$col] = $this->normalizeHeading((string) $col);
        }

        $mapping = [];
        $usedColumns = [];

        foreach (self::FIELD_KEYWORDS as $field => $keywords) {
            $bestColumn = null;
            $bestScore = 0;

            foreach ($normalizedColumns as $col => $normalized) {
                if (in_array($col, $usedColumns, true)) {
                    continue; // mỗi cột chỉ map cho 1 field
                }
                $score = $this->scoreColumn($normalized, $keywords);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestColumn = $col;
                }
            }

            // Ngưỡng tối thiểu để tránh map nhầm khi cột không liên quan.
            if ($bestColumn !== null && $bestScore >= 20) {
                $mapping[$field] = $bestColumn;
                $usedColumns[] = $bestColumn;
            }
        }

        return $mapping;
    }

    private function scoreColumn(string $normalized, array $keywords): int
    {
        $tokens = explode('_', $normalized);
        $best = 0;

        foreach ($keywords as $kw) {
            if ($kw === '') {
                continue;
            }
            if ($normalized === $kw) {
                $best = max($best, 100);

                continue;
            }
            if (str_starts_with($normalized, $kw) || str_ends_with($normalized, $kw)) {
                $best = max($best, 60);

                continue;
            }
            if (in_array($kw, $tokens, true)) {
                $best = max($best, 40);

                continue;
            }
            if (str_contains($normalized, $kw)) {
                $best = max($best, 20);
            }
        }

        return $best;
    }

    private function normalizeHeading(string $h): string
    {
        $s = strtolower(trim($h));
        $s = preg_replace('/[\s\-]+/', '_', $s) ?? $s;
        // bỏ dấu tiếng Việt cơ bản
        $from = ['à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ', 'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ', 'ì', 'í', 'ị', 'ỉ', 'ĩ', 'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ', 'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ', 'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ'];
        $to = ['a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y', 'd'];

        return str_replace($from, $to, $s);
    }

    private function generateSku(): string
    {
        return 'SP-'.strtoupper(Str::random(6));
    }
}
