<?php

namespace App\Modules\ShopProduct\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShopProduct\Imports\ShopProductImport;
use App\Modules\ShopProduct\Models\ShopEntry;
use App\Modules\ShopProduct\Validation\EntityRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller chung cho mọi entity admin lưu dạng JSON trên bảng shop_entries.
 *
 * Hỗ trợ list/show/CRUD/import-preview/import/export cho: customers, orders, suppliers,
 * news, promotions, sections, ... mà không cần build từng module riêng. Khi cần schema
 * mạnh (FK, validation chuyên sâu) thì build module riêng và bỏ entity ra whitelist.
 */
class GenericShopController extends Controller
{
    public function index(Request $request, string $entity): JsonResponse
    {
        $query = ShopEntry::query()->where('entity', $entity);

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where('search_text', 'ilike', "%{$q}%");
        }

        // Filter theo status / user_id (qua expression index — fast).
        if ($status = trim((string) $request->query('status', ''))) {
            $query->where('data->status', $status);
        }
        if ($userId = (int) $request->query('user_id', 0)) {
            $query->where(function ($q) use ($userId) {
                $q->where('data->user_id', $userId)
                    ->orWhere('data->user_id', (string) $userId);
            });
        }
        // group_id filter cho customers (so sánh cả int + string).
        if ($groupId = $request->query('group_id', null)) {
            $gid = (int) $groupId;
            if ($gid > 0) {
                $query->where(function ($q) use ($gid) {
                    $q->where('data->group_id', $gid)
                        ->orWhere('data->group_id', (string) $gid);
                });
            }
        }
        // active filter (boolean) — string 'true'/'false' hoặc 1/0.
        $activeQ = $request->query('active', null);
        if ($activeQ !== null && $activeQ !== '') {
            $val = filter_var($activeQ, FILTER_VALIDATE_BOOLEAN);
            $query->where('data->active', $val);
        }

        $query->orderByDesc('id');

        if ($request->boolean('all') || $entity === 'sections') {
            // Cho phép admin truyền `limit` để lấy nhiều hơn default. Cap ở 50k
            // để tránh phá memory; nếu cần nhiều hơn nên dùng pagination.
            $defaultCap = $entity === 'sections' ? 1000 : 5000;
            $cap = (int) $request->query('limit', $defaultCap);
            $cap = max(1, min($cap, 50000));
            $items = $query->limit($cap)->get();
            $data = $items->map(fn ($e) => $this->serialize($e));
            if ($entity === 'sections') {
                $data = $data->sortBy(fn ($row) => (int) ($row['sort_order'] ?? $row['sortOrder'] ?? 0))->values();
            }

            return response()->json([
                'success' => true,
                'data' => $data->all(),
                'meta' => [
                    'total' => $items->count(),
                    'perPage' => $items->count(),
                    'currentPage' => 1,
                    'lastPage' => 1,
                ],
                'stats' => $this->buildStats($entity),
            ]);
        }

        $perPage = (int) ($request->query('per_page') ?? 20);
        $perPage = max(1, min($perPage, 200));
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginator->items())->map(fn ($e) => $this->serialize($e))->all(),
            'meta' => [
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
            ],
            'stats' => $this->buildStats($entity),
        ]);
    }

    public function publicIndex(Request $request, string $entity): JsonResponse
    {
        $query = ShopEntry::query()->where('entity', $entity);

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where('search_text', 'ilike', "%{$q}%");
        }

        if ($entity === 'news') {
            $query->where('data->status', $request->query('status', 'published'));
        }
        if ($entity === 'promotions') {
            $query->whereIn('data->status', ['active', 'scheduled']);
        }

        $limit = max(1, min((int) $request->query('limit', 100), 200));
        $items = $query->orderByDesc('id')->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(fn ($e) => $this->serialize($e))->all(),
            'meta' => [
                'total' => $items->count(),
                'perPage' => $items->count(),
                'currentPage' => 1,
                'lastPage' => 1,
            ],
        ]);
    }

    public function showBySlug(string $entity, string $slug): JsonResponse
    {
        $entry = ShopEntry::where('entity', $entity)
            ->where('data->slug', $slug)
            ->first();

        if (! $entry) {
            return $this->notFound('Không tìm thấy bản ghi');
        }

        return $this->success($this->serialize($entry));
    }

    public function show(string $entity, int $id): JsonResponse
    {
        $entry = ShopEntry::where('entity', $entity)->find($id);
        if (! $entry) {
            return $this->notFound('Không tìm thấy bản ghi');
        }

        return $this->success($this->serialize($entry));
    }

    public function store(Request $request, string $entity): JsonResponse
    {
        $rules = EntityRules::rulesFor($entity, false);
        if ($rules) {
            Validator::make($request->all(), $rules)->validate();
        }
        $data = $this->normalizeData($request->all());
        $entry = ShopEntry::create([
            'entity' => $entity,
            'data' => $data,
            'search_text' => $this->buildSearchText($data),
        ]);

        return $this->success($this->serialize($entry), 'Đã tạo', 201);
    }

    public function update(Request $request, string $entity, int $id): JsonResponse
    {
        $entry = ShopEntry::where('entity', $entity)->find($id);
        if (! $entry) {
            return $this->notFound('Không tìm thấy bản ghi');
        }
        $rules = EntityRules::rulesFor($entity, true);
        if ($rules) {
            Validator::make($request->all(), $rules)->validate();
        }
        $data = $this->normalizeData(array_merge((array) $entry->data, $request->all()));
        $entry->data = $data;
        $entry->search_text = $this->buildSearchText($data);
        $entry->save();

        return $this->success($this->serialize($entry->fresh()), 'Đã cập nhật');
    }

    public function updateStatus(Request $request, string $entity, int $id): JsonResponse
    {
        $entry = ShopEntry::where('entity', $entity)->find($id);
        if (! $entry) {
            return $this->notFound('Không tìm thấy bản ghi');
        }

        $status = trim((string) $request->input('status', ''));
        if ($status === '') {
            return $this->error('Trạng thái không hợp lệ.', 422);
        }

        $data = (array) $entry->data;
        $data['status'] = $status;
        if ($entity === 'news' && $status === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now()->toDateString();
        }
        $entry->update([
            'data' => $data,
            'search_text' => $this->buildSearchText($data),
        ]);

        return $this->success($this->serialize($entry->fresh()), 'Đã cập nhật trạng thái');
    }

    public function updateSectionByKey(Request $request, string $key): JsonResponse
    {
        $payload = $this->normalizeData($request->all());
        $payload['section_key'] = $payload['section_key'] ?? $key;

        $entry = $this->findSectionByKey($key);
        if (! $entry) {
            $entry = ShopEntry::create([
                'entity' => 'sections',
                'data' => $payload,
                'search_text' => $this->buildSearchText($payload),
            ]);

            return $this->success($this->serialize($entry), 'Đã tạo khu vực hiển thị', 201);
        }

        $data = array_merge((array) $entry->data, $payload);
        $entry->update([
            'data' => $data,
            'search_text' => $this->buildSearchText($data),
        ]);

        return $this->success($this->serialize($entry->fresh()), 'Đã cập nhật khu vực hiển thị');
    }

    public function destroySectionByKey(string $key): JsonResponse
    {
        $entry = $this->findSectionByKey($key);
        if (! $entry) {
            return $this->notFound('Không tìm thấy khu vực hiển thị');
        }

        $entry->delete();

        return $this->success(null, 'Đã xóa khu vực hiển thị');
    }

    public function syncSectionProducts(Request $request, string $key): JsonResponse
    {
        $entry = $this->findSectionByKey($key);
        if (! $entry) {
            return $this->notFound('Không tìm thấy khu vực hiển thị');
        }

        $productIds = array_values(array_unique(array_map('intval', (array) $request->input('product_ids', []))));
        $data = (array) $entry->data;
        $data['product_ids'] = $productIds;
        $entry->update([
            'data' => $data,
            'search_text' => $this->buildSearchText($data),
        ]);

        return $this->success($this->serialize($entry->fresh()), 'Đã cập nhật sản phẩm trong khu vực');
    }

    public function reorderSections(Request $request): JsonResponse
    {
        $order = (array) $request->input('order', []);
        foreach (array_values($order) as $idx => $key) {
            $entry = $this->findSectionByKey((string) $key);
            if (! $entry) {
                continue;
            }
            $data = (array) $entry->data;
            $data['sort_order'] = $idx;
            $entry->update([
                'data' => $data,
                'search_text' => $this->buildSearchText($data),
            ]);
        }

        return $this->success(['order' => array_values($order)], 'Đã sắp xếp khu vực hiển thị');
    }

    public function destroy(string $entity, int $id): JsonResponse
    {
        $entry = ShopEntry::where('entity', $entity)->find($id);
        if (! $entry) {
            return $this->notFound('Không tìm thấy bản ghi');
        }
        $entry->delete();

        return $this->success(null, 'Đã xóa');
    }

    /**
     * Đọc file Excel/CSV → trả columns + sample rows + mapping mặc định.
     * Mapping mặc định = identity (mỗi column tự map vào field cùng tên đã normalize).
     */
    public function importPreview(Request $request, string $entity): JsonResponse
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

        // Field auto-suggested = từng column (FE có thể chỉnh).
        $fields = [];
        $mapping = [];
        foreach ($columns as $col) {
            $key = $this->slugifyKey((string) $col);
            $fields[] = ['key' => $key, 'label' => $col];
            $mapping[$key] = $col;
        }

        return $this->success([
            'columns' => $columns,
            'rows' => $rowsSample,
            'rowCount' => \count($reader->rows),
            'mapping' => $mapping,
            'fields' => $fields,
        ]);
    }

    public function import(Request $request, string $entity): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            'mapping' => ['required', 'string'],
        ]);

        $mapping = json_decode((string) $request->input('mapping'), true);
        if (! \is_array($mapping) || empty($mapping)) {
            return $this->error('Mapping không hợp lệ.', 422);
        }
        $updateExisting = (bool) $request->input('update_existing', false);
        $idField = (string) ($request->input('id_field') ?? '');

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
                $payload = [];
                foreach ($mapping as $field => $column) {
                    if (! $column || ! \array_key_exists($column, $row)) {
                        continue;
                    }
                    $value = $row[$column];
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $payload[$field] = \is_string($value) ? trim($value) : $value;
                }
                if (empty($payload)) {
                    $skipped++;

                    continue;
                }

                $matchValue = $idField !== '' ? ($payload[$idField] ?? null) : null;
                $existing = null;
                if ($matchValue !== null && $matchValue !== '') {
                    $existing = ShopEntry::where('entity', $entity)
                        ->whereJsonContains('data->'.$idField, $matchValue)
                        ->first();
                }

                if ($existing) {
                    if ($updateExisting) {
                        $existing->data = array_merge((array) $existing->data, $payload);
                        $existing->search_text = $this->buildSearchText((array) $existing->data);
                        $existing->save();
                        $updated++;
                    } else {
                        $skipped++;
                    }

                    continue;
                }

                ShopEntry::create([
                    'entity' => $entity,
                    'data' => $payload,
                    'search_text' => $this->buildSearchText($payload),
                ]);
                $created++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->error('Lỗi khi nhập: '.$e->getMessage(), 500);
        }

        return $this->success([
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ], 'Đã nhập dữ liệu');
    }

    public function export(string $entity): StreamedResponse
    {
        $filename = "shop-{$entity}-".date('Ymd').'.csv';

        return response()->streamDownload(function () use ($entity) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

            // Lấy union các key trong data của 200 dòng đầu để làm header CSV.
            $sample = ShopEntry::where('entity', $entity)->limit(200)->get();
            $keys = [];
            foreach ($sample as $entry) {
                foreach (array_keys((array) $entry->data) as $k) {
                    if (! \in_array($k, $keys, true)) {
                        $keys[] = $k;
                    }
                }
            }
            fputcsv($out, array_merge(['id'], $keys));

            ShopEntry::where('entity', $entity)->orderBy('id')->chunk(500, function ($chunk) use ($out, $keys) {
                foreach ($chunk as $entry) {
                    $row = [(string) $entry->id];
                    $data = (array) $entry->data;
                    foreach ($keys as $k) {
                        $value = $data[$k] ?? '';
                        if (\is_array($value)) {
                            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                        }
                        $row[] = (string) $value;
                    }
                    fputcsv($out, $row);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ───────── helpers ─────────

    private function serialize(ShopEntry $entry): array
    {
        return array_merge(
            ['id' => $entry->id],
            (array) $entry->data,
            [
                'createdAt' => $entry->created_at?->toIso8601String(),
                'updatedAt' => $entry->updated_at?->toIso8601String(),
            ]
        );
    }

    private function buildStats(string $entity): array
    {
        $total = ShopEntry::where('entity', $entity)->count();

        return ['total' => $total];
    }

    private function normalizeData(array $raw): array
    {
        // Bỏ các key meta không thuộc data thật.
        unset($raw['id'], $raw['createdAt'], $raw['updatedAt'], $raw['created_at'], $raw['updated_at']);

        return $raw;
    }

    private function findSectionByKey(string $key): ?ShopEntry
    {
        $entry = ShopEntry::where('entity', 'sections')
            ->where('data->section_key', $key)
            ->first();

        if (! $entry && ctype_digit($key)) {
            $entry = ShopEntry::where('entity', 'sections')->find((int) $key);
        }

        return $entry;
    }

    private function buildSearchText(array $data): string
    {
        $flat = [];
        array_walk_recursive($data, static function ($v) use (&$flat) {
            if (\is_scalar($v)) {
                $flat[] = (string) $v;
            }
        });

        return mb_substr(implode(' ', $flat), 0, 4000);
    }

    private function slugifyKey(string $h): string
    {
        // mb_strtolower xử lý đúng "Đ"/"Ă"/"Â"/... (strtolower bỏ qua multibyte → ký tự
        // hoa Việt bị regex sau strip → mất luôn ký tự, vd "Điện thoại" → "ien_thoai").
        $s = mb_strtolower(trim($h), 'UTF-8');
        $s = preg_replace('/[\s\-\/]+/u', '_', $s) ?? $s;
        $from = ['à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ', 'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ', 'ì', 'í', 'ị', 'ỉ', 'ĩ', 'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ', 'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ', 'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ'];
        $to = ['a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y', 'd'];
        $s = str_replace($from, $to, $s);

        return preg_replace('/[^a-z0-9_]/', '', $s) ?: 'col';
    }
}
