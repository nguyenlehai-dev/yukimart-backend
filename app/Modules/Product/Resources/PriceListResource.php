<?php

namespace App\Modules\Product\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'is_default' => $this->is_default,
            'is_active_now' => $this->isActive(),
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'base_price_list' => $this->whenLoaded('basePriceList', fn () => [
                'id' => $this->basePriceList->id,
                'name' => $this->basePriceList->name,
            ]),
            'formula_type' => $this->formula_type,
            'formula_value' => $this->formula_value ? (float) $this->formula_value : null,
            'auto_update_from_base' => $this->auto_update_from_base,
            'add_products_from_base' => $this->add_products_from_base,
            'rounding_type' => $this->rounding_type,
            'rounding_method' => $this->rounding_method,
            'cashier_policy' => $this->cashier_policy,
            'items_count' => $this->whenCounted('items'),
            'items' => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_code' => $item->relationLoaded('product') ? $item->product->code : null,
                    'product_name' => $item->relationLoaded('product') ? $item->product->name : null,
                    'variant_id' => $item->variant_id,
                    'variant_name' => $item->relationLoaded('variant') && $item->variant ? $item->variant->name : null,
                    'unit_id' => $item->unit_id,
                    'unit_name' => $item->relationLoaded('unit') && $item->unit ? $item->unit->name : null,
                    'price' => (float) $item->price,
                    'item_formula_type' => $item->item_formula_type,
                    'item_formula_value' => $item->item_formula_value ? (float) $item->item_formula_value : null,
                ])
            ),
            'organizations' => $this->whenLoaded('organizations', fn () =>
                $this->organizations->map(fn ($o) => ['id' => $o->id, 'name' => $o->name])
            ),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
