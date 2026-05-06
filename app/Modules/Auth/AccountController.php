<?php

namespace App\Modules\Auth;

use App\Http\Controllers\Controller;
use App\Modules\ShopProduct\Models\ShopEntry;
use App\Modules\ShopProduct\Models\ShopProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * AccountController — Endpoint dành cho user thường (đã đăng nhập) đọc dữ liệu
 * scope theo chính họ: orders, activities, purchase-history, invoices.
 *
 * Mọi truy vấn đều filter `data->user_id = Auth::id()` để mỗi user chỉ thấy
 * dữ liệu của riêng mình. Khi admin tạo/import bản ghi qua admin panel với
 * trường user_id trỏ tới customer thì bản ghi đó tự xuất hiện trong tài khoản
 * của customer.
 */
class AccountController extends Controller
{
    public function orders(Request $request): JsonResponse
    {
        $items = $this->scoped('orders')
            ->orderByDesc('id')
            ->limit($this->limit($request))
            ->get();

        return $this->success([
            'items' => $items->map(fn ($e) => $this->serializeEntry($e))->all(),
        ]);
    }

    public function activities(Request $request): JsonResponse
    {
        // Hoạt động = các bình luận / đánh giá user đã để lại trên sản phẩm.
        $items = $this->scoped('product-comments')
            ->orderByDesc('id')
            ->limit($this->limit($request))
            ->get();

        return $this->success([
            'items' => $items->map(fn ($e) => $this->serializeEntry($e))->all(),
        ]);
    }

    public function purchaseHistory(Request $request): JsonResponse
    {
        // Tổng lịch sử mua: gồm đơn sản phẩm (orders) + đơn dịch vụ / gói
        // (service-purchases). Map về cùng shape cho UI tab Lịch sử mua hàng.
        $services = $this->scoped('service-purchases')->get()
            ->map(fn ($e) => $this->mapServicePurchase($e));

        $orders = $this->scoped('orders')->get()
            ->map(fn ($e) => $this->mapOrderAsPurchase($e));

        $items = $services->concat($orders)
            ->sortByDesc(fn ($row) => $row['_sort'] ?? 0)
            ->take($this->limit($request))
            ->map(function ($row) {
                unset($row['_sort']);

                return $row;
            })
            ->values()
            ->all();

        return $this->success(['items' => $items]);
    }

    private function mapServicePurchase(ShopEntry $entry): array
    {
        $data = (array) $entry->data;

        return [
            'id' => $entry->id,
            'name' => (string) ($data['name'] ?? 'Dịch vụ'),
            'category' => (string) ($data['category'] ?? 'new'),
            'date' => (string) ($data['date'] ?? ($entry->created_at?->format('d/m/Y') ?? '')),
            'amount' => (float) ($data['amount'] ?? 0),
            'createdAt' => $entry->created_at?->toIso8601String(),
            '_sort' => $entry->created_at?->getTimestamp() ?? 0,
        ];
    }

    private function mapOrderAsPurchase(ShopEntry $entry): array
    {
        $data = (array) $entry->data;
        $details = (array) ($data['items_detail'] ?? []);
        $name = $details
            ? collect($details)->pluck('name')->filter()->take(2)->implode(', ')
                .(\count($details) > 2 ? ' …' : '')
            : 'Đơn hàng';

        return [
            'id' => $entry->id,
            'name' => 'Đơn hàng '.($data['code'] ?? '#'.$entry->id).' — '.$name,
            'category' => 'new',
            'date' => $entry->created_at?->format('d/m/Y') ?? '',
            'amount' => (float) ($data['total'] ?? 0),
            'createdAt' => $entry->created_at?->toIso8601String(),
            '_sort' => $entry->created_at?->getTimestamp() ?? 0,
        ];
    }

    public function invoices(Request $request): JsonResponse
    {
        $items = $this->scoped('invoices')
            ->orderByDesc('id')
            ->limit($this->limit($request))
            ->get();

        return $this->success([
            'items' => $items->map(fn ($e) => $this->serializeEntry($e))->all(),
        ]);
    }

    /**
     * Tạo đơn hàng cho user đang đăng nhập (từ flow checkout). user_id luôn ép
     * theo Auth::id(), không tin client.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required'],
            'items.*.name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.image' => ['nullable', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:64'],
            'customer' => ['required', 'array'],
            'customer.firstName' => ['required', 'string', 'max:120'],
            'customer.lastName' => ['required', 'string', 'max:120'],
            'customer.phone' => ['required', 'string', 'max:32'],
            'customer.email' => ['required', 'email'],
            'customer.address' => ['required', 'string', 'max:255'],
            'customer.city' => ['required', 'string', 'max:120'],
            'customer.country' => ['nullable', 'string', 'max:120'],
            'customer.company' => ['nullable', 'string', 'max:120'],
            'customer.postalCode' => ['nullable', 'string', 'max:32'],
            'customer.note' => ['nullable', 'string', 'max:2000'],
        ]);

        $userId = (int) Auth::id();
        $itemsCount = collect($validated['items'])->sum('quantity');
        $customer = $validated['customer'];
        $fullName = trim(($customer['firstName'] ?? '').' '.($customer['lastName'] ?? ''));

        $data = [
            'user_id' => $userId,
            'code' => $this->generateOrderCode(),
            'status' => 'processing',
            'payment_method' => match ($validated['payment_method']) {
                'bank_transfer' => 'Chuyển khoản ngân hàng',
                'cod' => 'Trả tiền mặt khi nhận hàng',
                default => $validated['payment_method'],
            },
            'payment' => match ($validated['payment_method']) {
                'bank_transfer' => 'bank',
                'cod' => 'cod',
                default => 'cod',
            },
            'items' => (int) $itemsCount,
            'subtotal' => (float) $validated['subtotal'],
            'discount' => 0,
            'total' => (float) $validated['total'],
            'items_detail' => array_map(fn ($i) => [
                'id' => $i['id'],
                'name' => $i['name'],
                'quantity' => (int) $i['quantity'],
                'price' => (float) $i['price'],
                'image' => $i['image'] ?? null,
            ], $validated['items']),
            'customer' => $customer,
            // Flat field cho admin OrdersView (useImportedAs dùng key phẳng).
            'customer_id' => $userId,
            'customer_name' => $fullName !== '' ? $fullName : ($customer['email'] ?? ''),
            'customer_email' => $customer['email'] ?? '',
            'customer_phone' => $customer['phone'] ?? '',
            'customer_address' => trim(
                ($customer['address'] ?? '').
                ($customer['city'] ?? '' ? ', '.$customer['city'] : '').
                ($customer['country'] ?? '' ? ', '.$customer['country'] : '')
            ),
            'note' => $customer['note'] ?? null,
            // Snake-case timestamp cho admin view (candidate "created_at").
            'created_at' => now()->toIso8601String(),
        ];

        // Atomic: tạo đơn + trừ tồn kho + sync customer info trong cùng 1 tx.
        // Nếu bất kỳ bước nào fail (vd hết hàng), toàn bộ rollback.
        try {
            $entry = DB::transaction(function () use ($data, $validated, $userId, $customer) {
                $entry = ShopEntry::create([
                    'entity' => 'orders',
                    'data' => $data,
                    'search_text' => $this->buildSearchText($data),
                ]);

                $this->decrementStock($validated['items'], (string) $data['code']);
                $this->syncCustomerContact($userId, $customer, (float) $validated['total']);

                return $entry;
            });
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(['order' => $this->serializeEntry($entry)], 'Đã đặt đơn', 201);
    }

    /**
     * Trừ tồn kho atomic: lock từng SP, kiểm tra đủ stock, decrement. Throw nếu
     * không đủ → transaction outer rollback đơn hàng. Đồng thời log movement
     * vào entity inventory-history để báo cáo tồn kho trace được.
     */
    private function decrementStock(array $items, ?string $orderCode = null): void
    {
        foreach ($items as $i) {
            $productId = (int) ($i['id'] ?? 0);
            $qty = (int) ($i['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            // lockForUpdate tránh race khi 2 user đặt cùng SP cùng lúc.
            $product = ShopProduct::query()->whereKey($productId)->lockForUpdate()->first();
            if (! $product) {
                continue;
            }
            if ((int) $product->stock < $qty) {
                throw new \RuntimeException("Sản phẩm \"{$product->name}\" chỉ còn {$product->stock} (yêu cầu {$qty}).");
            }
            $product->stock = (int) $product->stock - $qty;
            $product->save();

            $this->logInventoryMovement([
                'product_id' => $productId,
                'sku' => $product->sku,
                'name' => $product->name,
                'type' => 'out',
                'quantity' => $qty,
                'ref_code' => $orderCode ?? '',
                'ref_type' => 'order',
                'reason' => 'Khách đặt đơn từ website',
            ]);
        }
    }

    private function logInventoryMovement(array $data): void
    {
        $data['created_at'] = now()->toIso8601String();
        ShopEntry::create([
            'entity' => 'inventory-history',
            'data' => $data,
            'search_text' => mb_substr(implode(' ', array_filter([
                $data['sku'] ?? null, $data['name'] ?? null, $data['ref_code'] ?? null, $data['reason'] ?? null,
            ])), 0, 4000),
        ]);
    }

    /**
     * Cập nhật phone/address mới nhất từ checkout vào ShopEntry customer.
     * Cộng dồn orders_count + total_spent để admin CRM thấy đúng.
     */
    private function syncCustomerContact(int $userId, array $customer, float $orderTotal = 0): void
    {
        $entry = ShopEntry::where('entity', 'customers')
            ->where(function ($q) use ($userId) {
                $q->where('data->user_id', $userId)
                    ->orWhere('data->user_id', (string) $userId);
            })
            ->first();

        if (! $entry) {
            return;
        }

        $data = (array) $entry->data;
        $fullName = trim(($customer['firstName'] ?? '').' '.($customer['lastName'] ?? ''));
        if ($fullName !== '') {
            $data['name'] = $fullName;
        }
        if (! empty($customer['phone'])) {
            $data['phone'] = $customer['phone'];
        }
        if (! empty($customer['email'])) {
            $data['email'] = $customer['email'];
        }
        $address = trim(
            ($customer['address'] ?? '').
            ($customer['city'] ?? '' ? ', '.$customer['city'] : '').
            ($customer['country'] ?? '' ? ', '.$customer['country'] : '')
        );
        if ($address !== '') {
            $data['address'] = $address;
        }
        if (! empty($customer['note'])) {
            $data['note'] = $customer['note'];
        }

        // Bumps counters từ đơn vừa đặt
        if ($orderTotal > 0) {
            $data['orders_count'] = (int) ($data['orders_count'] ?? 0) + 1;
            $data['total_spent'] = (float) ($data['total_spent'] ?? 0) + $orderTotal;
        }

        $entry->update([
            'data' => $data,
            'search_text' => mb_substr(implode(' ', array_filter([
                $data['name'] ?? null, $data['email'] ?? null, $data['phone'] ?? null, $data['address'] ?? null,
            ])), 0, 4000),
        ]);
    }

    private function generateOrderCode(): string
    {
        // Format gọn cho UI: YM-{yymmdd}-{rand5}
        $rand = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

        return 'YM-'.date('ymd').'-'.$rand;
    }

    private function buildSearchText(array $data): string
    {
        $flat = [];
        array_walk_recursive($data, static function ($v) use (&$flat) {
            if (is_scalar($v)) {
                $flat[] = (string) $v;
            }
        });

        return mb_substr(implode(' ', $flat), 0, 4000);
    }

    /**
     * Build query filter ShopEntry theo entity + user_id của user đang đăng nhập.
     * PostgreSQL: `data->user_id` so sánh dạng số qua casting.
     */
    private function scoped(string $entity): Builder
    {
        $userId = (int) Auth::id();

        return ShopEntry::query()
            ->where('entity', $entity)
            ->where(function ($q) use ($userId) {
                $q->where('data->user_id', $userId)
                    ->orWhere('data->user_id', (string) $userId);
            });
    }

    private function limit(Request $request): int
    {
        $limit = (int) $request->query('limit', 100);

        return max(1, min($limit, 500));
    }

    private function serializeEntry(ShopEntry $entry): array
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
}
