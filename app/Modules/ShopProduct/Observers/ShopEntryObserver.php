<?php

namespace App\Modules\ShopProduct\Observers;

use App\Modules\ShopProduct\Models\ShopEntry;
use App\Modules\ShopProduct\Models\ShopProduct;
use Illuminate\Validation\ValidationException;

/**
 * Observer cho shop_entries entity = orders:
 * - Khi đổi status sang/từ "cancelled": điều chỉnh counters customer + restore/trừ tồn.
 * - Khi xoá đơn: rollback counters + restore tồn (nếu đơn không phải cancelled).
 *
 * Logic dồn ở 1 nơi để mọi mutation (admin update, FE checkout, import) đều
 * giữ counter đồng bộ.
 */
class ShopEntryObserver
{
    /**
     * State machine cho order status. Mỗi key = status hiện tại; value = list
     * status được phép chuyển sang. Chặn transition phi lý (vd completed →
     * pending) và transition không an toàn (vd completed → cancelled mà không
     * đi qua refund flow).
     */
    private const ORDER_STATUS_TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipping', 'cancelled'],
        'shipping' => ['completed', 'cancelled'],
        'completed' => [], // terminal — chỉ cho phép khi tạo sales-return
        'cancelled' => ['pending'], // cho phép un-cancel chuyển về pending
    ];

    public function updating(ShopEntry $entry): void
    {
        if ($entry->entity !== 'orders') {
            return;
        }
        $original = (array) ($entry->getOriginal('data') ?? []);
        if (is_string($original)) {
            $original = json_decode($original, true) ?: [];
        }
        $current = (array) ($entry->data ?? []);

        $oldStatus = (string) ($original['status'] ?? '');
        $newStatus = (string) ($current['status'] ?? '');
        if ($oldStatus === $newStatus) {
            return;
        }

        // Validate transition theo state machine.
        if ($oldStatus !== '' && isset(self::ORDER_STATUS_TRANSITIONS[$oldStatus])) {
            $allowed = self::ORDER_STATUS_TRANSITIONS[$oldStatus];
            if (! in_array($newStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => ["Không thể chuyển trạng thái từ \"{$oldStatus}\" sang \"{$newStatus}\". Cho phép: ".(empty($allowed) ? 'none' : implode(', ', $allowed))],
                ]);
            }
        }

        // active -> cancelled: rollback counters + restore tồn
        if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') {
            $this->adjustCustomerCounters($current, -1);
            $this->restoreStock($current);
        }
        // cancelled -> active: cộng lại counters + trừ tồn
        elseif ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
            $this->adjustCustomerCounters($current, +1);
            $this->decrementStock($current);
        }
    }

    public function deleting(ShopEntry $entry): void
    {
        if ($entry->entity !== 'orders') {
            return;
        }
        $data = (array) ($entry->data ?? []);
        $status = (string) ($data['status'] ?? '');
        // Đơn đang active mới cần rollback counters + restore tồn.
        if ($status !== 'cancelled') {
            $this->adjustCustomerCounters($data, -1);
            $this->restoreStock($data);
        }
    }

    private function adjustCustomerCounters(array $orderData, int $sign): void
    {
        $userId = (int) ($orderData['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }
        $entry = ShopEntry::where('entity', 'customers')
            ->where(function ($q) use ($userId) {
                $q->where('data->user_id', $userId)
                    ->orWhere('data->user_id', (string) $userId);
            })
            ->first();
        if (! $entry) {
            return;
        }
        $cd = (array) $entry->data;
        $total = (float) ($orderData['total'] ?? 0);
        $cd['orders_count'] = max(0, (int) ($cd['orders_count'] ?? 0) + $sign);
        $cd['total_spent'] = max(0, (float) ($cd['total_spent'] ?? 0) + ($sign * $total));
        $entry->update(['data' => $cd]);
    }

    private function restoreStock(array $orderData): void
    {
        $items = (array) ($orderData['items_detail'] ?? []);
        $orderCode = (string) ($orderData['code'] ?? '');
        foreach ($items as $it) {
            $pid = (int) ($it['id'] ?? 0);
            $qty = (int) ($it['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                continue;
            }
            $product = ShopProduct::find($pid);
            if (! $product) {
                continue;
            }
            $product->stock = (int) $product->stock + $qty;
            $product->save();

            $this->logMovement($product, 'in', $qty, $orderCode, 'order_cancelled', 'Hoàn kho do huỷ đơn');
        }
    }

    private function decrementStock(array $orderData): void
    {
        $items = (array) ($orderData['items_detail'] ?? []);
        $orderCode = (string) ($orderData['code'] ?? '');
        foreach ($items as $it) {
            $pid = (int) ($it['id'] ?? 0);
            $qty = (int) ($it['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                continue;
            }
            $product = ShopProduct::find($pid);
            if (! $product) {
                continue;
            }
            $product->stock = max(0, (int) $product->stock - $qty);
            $product->save();

            $this->logMovement($product, 'out', $qty, $orderCode, 'order_uncancelled', 'Trừ kho do mở lại đơn');
        }
    }

    private function logMovement(ShopProduct $product, string $type, int $qty, string $refCode, string $refType, string $reason): void
    {
        ShopEntry::create([
            'entity' => 'inventory-history',
            'data' => [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'type' => $type,
                'quantity' => $qty,
                'ref_code' => $refCode,
                'ref_type' => $refType,
                'reason' => $reason,
                'created_at' => now()->toIso8601String(),
            ],
            'search_text' => mb_substr($product->sku.' '.$product->name.' '.$refCode, 0, 4000),
        ]);
    }
}
