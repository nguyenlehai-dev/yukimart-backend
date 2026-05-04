<?php

namespace App\Modules\ShopProduct\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShopProduct\Imports\ShopProductImport;
use App\Modules\ShopProduct\Models\ShopEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $query->orderByDesc('id');

        if ($request->boolean('all')) {
            $items = $query->limit(500)->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn ($e) => $this->serialize($e))->all(),
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
        $data = $this->normalizeData(array_merge((array) $entry->data, $request->all()));
        $entry->data = $data;
        $entry->search_text = $this->buildSearchText($data);
        $entry->save();

        return $this->success($this->serialize($entry->fresh()), 'Đã cập nhật');
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
