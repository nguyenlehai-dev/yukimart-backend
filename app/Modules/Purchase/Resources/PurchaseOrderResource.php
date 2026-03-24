<?php

namespace App\Modules\Purchase\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier ? [
                'id' => $this->supplier->id,
                'code' => $this->supplier->code,
                'name' => $this->supplier->name,
                'phone' => $this->supplier->phone,
                'address' => $this->supplier->address,
            ] : null),
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
            ]),
            'total_amount' => (float) $this->total_amount,
            'discount' => (float) $this->discount,
            'paid_amount' => (float) $this->paid_amount,
            'debt_amount' => (float) $this->debt_amount,
            'note' => $this->note,
            'order_date' => $this->order_date?->toISOString(),
            'items_count' => $this->whenCounted('items'),
            'items' => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_code' => $item->product?->code,
                    'product_name' => $item->product?->name,
                    'variant_id' => $item->variant_id,
                    'variant_name' => $item->variant?->name,
                    'unit_id' => $item->unit_id,
                    'unit_name' => $item->unit?->name,
                    'quantity' => (float) $item->quantity,
                    'price' => (float) $item->price,
                    'discount' => (float) $item->discount,
                    'amount' => (float) $item->amount,
                ])
            ),
            'returns' => $this->whenLoaded('returns', fn () =>
                $this->returns->map(fn ($r) => [
                    'id' => $r->id,
                    'code' => $r->code,
                    'status' => $r->status,
                    'total_amount' => (float) $r->total_amount,
                ])
            ),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
