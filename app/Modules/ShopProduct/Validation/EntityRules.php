<?php

namespace App\Modules\ShopProduct\Validation;

/**
 * Validation rules theo entity cho GenericShopController. Giữ generic JSON
 * storage nhưng các trường critical được kiểm tra để tránh garbage data.
 *
 * Trả về null = entity không có rule cụ thể (skip validation, giữ behavior cũ).
 */
class EntityRules
{
    public static function rulesFor(string $entity, bool $forUpdate = false): ?array
    {
        $rules = match ($entity) {
            'customers' => [
                'name' => [$forUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:32'],
                'group_id' => ['nullable', 'integer', 'min:0'],
                'orders_count' => ['nullable', 'integer', 'min:0'],
                'total_spent' => ['nullable', 'numeric', 'min:0'],
                'active' => ['nullable', 'boolean'],
            ],
            'customer-groups' => [
                'code' => [$forUpdate ? 'sometimes' : 'required', 'string', 'max:32'],
                'name' => [$forUpdate ? 'sometimes' : 'required', 'string', 'max:120'],
                'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'min_spent' => ['nullable', 'numeric', 'min:0'],
                'active' => ['nullable', 'boolean'],
            ],
            'orders' => [
                'code' => ['nullable', 'string', 'max:64'],
                'status' => ['nullable', 'in:pending,processing,shipping,completed,cancelled'],
                'total' => ['nullable', 'numeric', 'min:0'],
                'subtotal' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'payment' => ['nullable', 'in:cod,bank,momo,card'],
                'customer_email' => ['nullable', 'email'],
                'customer_phone' => ['nullable', 'string', 'max:32'],
                'invoice_id' => ['nullable', 'integer', 'min:0'],
                'shipment_id' => ['nullable', 'integer', 'min:0'],
            ],
            'invoices' => [
                'code' => [$forUpdate ? 'sometimes' : 'required', 'string', 'max:64'],
                'status' => ['nullable', 'in:unpaid,paid,partial'],
                'total' => ['nullable', 'numeric', 'min:0'],
                'paid' => ['nullable', 'numeric', 'min:0'],
                'vat' => ['nullable', 'numeric', 'min:0'],
                'order_id' => ['nullable', 'integer', 'min:0'],
            ],
            'shipments' => [
                'tracking_code' => [$forUpdate ? 'sometimes' : 'required', 'string', 'max:64'],
                'status' => ['nullable', 'in:pending,picking,shipping,delivered,failed,returned'],
                'fee' => ['nullable', 'numeric', 'min:0'],
                'cod' => ['nullable', 'numeric', 'min:0'],
                'order_id' => ['nullable', 'integer', 'min:0'],
            ],
            'sales-returns' => [
                'code' => [$forUpdate ? 'sometimes' : 'required', 'string', 'max:64'],
                'status' => ['nullable', 'in:pending,approved,completed,rejected'],
                'total' => ['nullable', 'numeric', 'min:0'],
                'refunded' => ['nullable', 'numeric', 'min:0'],
                'invoice_id' => ['nullable', 'integer', 'min:0'],
            ],
            'finance-expenses' => [
                'name' => [$forUpdate ? 'sometimes' : 'required', 'string', 'max:120'],
                'value' => [$forUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
                'color' => ['nullable', 'string', 'max:16'],
            ],
            default => null,
        };

        return $rules;
    }
}
