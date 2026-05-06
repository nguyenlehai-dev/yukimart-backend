<?php

namespace App\Modules\ShopProduct\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ShopProduct\Models\ShopEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCommentController extends Controller
{
    private const ENTITY = 'product-comments';

    public function index(Request $request): JsonResponse
    {
        $query = ShopEntry::query()
            ->where('entity', self::ENTITY)
            ->where('data->status', 'approved');

        if ($request->filled('product_id')) {
            $query->where('data->product_id', (int) $request->query('product_id'));
        }
        if ($request->filled('type')) {
            $query->where('data->type', (string) $request->query('type'));
        }

        $limit = max(1, min((int) $request->query('limit', 100), 200));
        $items = $query->orderByDesc('id')->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(fn ($entry) => $this->serialize($entry))->all(),
            'meta' => [
                'total' => $items->count(),
                'perPage' => $items->count(),
                'currentPage' => 1,
                'lastPage' => 1,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'product_sku' => ['nullable', 'string', 'max:64'],
            'type' => ['required', 'in:review,question'],
            'author' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        if (($data['type'] ?? '') === 'review' && empty($data['rating'])) {
            return $this->error('Đánh giá cần có số sao.', 422);
        }

        // Auto-approve: bình luận khách gửi từ trang chi tiết sản phẩm sẽ hiển thị
        // ngay lập tức. Admin chỉ vào trang quản lý để theo dõi/ẩn/đánh spam khi
        // có nội dung không phù hợp, không cần quy trình duyệt.
        $payload = array_merge($data, [
            'status' => 'approved',
            'verified' => false,
            'likes' => 0,
            'reply' => '',
            'date' => now()->format('d/m/Y'),
        ]);

        $entry = ShopEntry::create([
            'entity' => self::ENTITY,
            'data' => $payload,
            'search_text' => $this->buildSearchText($payload),
        ]);

        return $this->success($this->serialize($entry), 'Bình luận đã được đăng.', 201);
    }

    /**
     * Khách trả lời 1 bình luận đã có (review/question). Reply được push vào
     * mảng `data.replies` của entry gốc — giữ nguyên `data.reply` (text) của
     * shop để admin vẫn quản lý phản hồi chính thức từ YukiMart riêng.
     */
    public function reply(Request $request, int $id): JsonResponse
    {
        $entry = ShopEntry::query()
            ->where('entity', self::ENTITY)
            ->where('data->status', 'approved')
            ->find($id);
        if (! $entry) {
            return $this->notFound('Không tìm thấy bình luận');
        }

        $data = $request->validate([
            'author' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $payload = (array) $entry->data;
        $replies = array_values((array) ($payload['replies'] ?? []));
        $replies[] = [
            // Sinh id duy nhất theo timestamp ms — không trùng với id YukiMart auto
            // sinh ($entry->id * 1000) trong serialize().
            'id' => (int) (microtime(true) * 1000),
            'author' => $data['author'],
            'isStore' => false,
            'date' => now()->format('d/m/Y'),
            'content' => $data['content'],
            'likes' => 0,
        ];
        $payload['replies'] = $replies;

        $entry->data = $payload;
        $entry->search_text = $this->buildSearchText($payload);
        $entry->save();

        return $this->success($this->serialize($entry->fresh()), 'Trả lời đã được đăng.', 201);
    }

    private function serialize(ShopEntry $entry): array
    {
        $data = (array) $entry->data;
        $reply = trim((string) ($data['reply'] ?? ''));
        $replies = (array) ($data['replies'] ?? []);

        if ($reply !== '') {
            $replies[] = [
                'id' => (int) $entry->id * 1000,
                'author' => 'YukiMart',
                'isStore' => true,
                'date' => (string) ($data['reply_date'] ?? $entry->updated_at?->format('d/m/Y')),
                'content' => $reply,
                'likes' => 0,
            ];
        }

        return [
            'id' => (int) $entry->id,
            'productId' => (int) ($data['product_id'] ?? $data['productId'] ?? 0),
            'productName' => (string) ($data['product_name'] ?? $data['productName'] ?? ''),
            'productSku' => (string) ($data['product_sku'] ?? $data['productSku'] ?? ''),
            'type' => (string) ($data['type'] ?? 'review'),
            'author' => (string) ($data['author'] ?? 'Khách hàng'),
            'rating' => (int) ($data['rating'] ?? 0),
            'date' => (string) ($data['date'] ?? $entry->created_at?->format('d/m/Y')),
            'content' => (string) ($data['content'] ?? ''),
            'verified' => (bool) ($data['verified'] ?? false),
            'likes' => (int) ($data['likes'] ?? 0),
            'reply' => $reply,
            'replies' => $replies,
            'status' => (string) ($data['status'] ?? 'pending'),
            'createdAt' => $entry->created_at?->toIso8601String(),
            'updatedAt' => $entry->updated_at?->toIso8601String(),
        ];
    }

    private function buildSearchText(array $data): string
    {
        return mb_substr(implode(' ', array_map(static fn ($v) => is_scalar($v) ? (string) $v : '', $data)), 0, 4000);
    }
}
