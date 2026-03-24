<?php

namespace App\Modules\Purchase\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'code' => $this->supplier->code,
                'name' => $this->supplier->name,
                'phone' => $this->supplier->phone ?? null,
            ]),
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
            ]),
            'purchase_order' => $this->whenLoaded('purchaseOrder', fn () => $this->purchaseOrder ? [
                'id' => $this->purchaseOrder->id,
                'code' => $this->purchaseOrder->code,
            ] : null),
            'total_amount' => (float) $this->total_amount,
            'supplier_paid' => (float) $this->supplier_paid,
            'debt_amount' => (float) $this->debt_amount,
            'note' => $this->note,
            'return_date' => $this->return_date?->toISOString(),
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
                    'amount' => (float) $item->amount,
                ])
            ),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
