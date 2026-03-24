<?php

namespace App\Modules\Product\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category?->slug,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand?->slug,
            ]),
            'base_unit' => $this->whenLoaded('baseUnit', fn () => [
                'id' => $this->baseUnit->id,
                'name' => $this->baseUnit->name,
            ]),
            'base_price' => (float) $this->base_price,
            'cost_price' => (float) $this->cost_price,
            'weight' => $this->weight ? (float) $this->weight : null,
            'allow_negative_stock' => $this->allow_negative_stock,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'point' => $this->point,
            'variants_count' => $this->whenCounted('variants'),
            'variants' => $this->whenLoaded('variants', fn () =>
                $this->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'barcode' => $v->barcode,
                    'price' => (float) $v->price,
                    'cost_price' => (float) $v->cost_price,
                    'name' => $v->name,
                    'is_active' => $v->is_active,
                    'attributes' => $v->relationLoaded('attributeValues')
                        ? $v->attributeValues->map(fn ($av) => [
                            'attribute_id' => $av->attribute_id,
                            'attribute_name' => $av->relationLoaded('attribute') ? $av->attribute->name : null,
                            'value_id' => $av->id,
                            'value' => $av->value,
                        ]) : [],
                ])
            ),
            'components' => $this->whenLoaded('components', fn () =>
                $this->components->map(fn ($c) => [
                    'id' => $c->id,
                    'product_id' => $c->component_product_id,
                    'product_name' => $c->relationLoaded('componentProduct') ? $c->componentProduct->name : null,
                    'product_code' => $c->relationLoaded('componentProduct') ? $c->componentProduct->code : null,
                    'quantity' => (float) $c->quantity,
                    'unit' => $c->relationLoaded('unit') && $c->unit ? [
                        'id' => $c->unit->id,
                        'name' => $c->unit->name,
                    ] : null,
                ])
            ),
            'locations' => $this->whenLoaded('locations', fn () =>
                $this->locations->map(fn ($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                ])
            ),
            'unit_conversions' => $this->whenLoaded('unitConversions', fn () =>
                $this->unitConversions->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'conversion_value' => (float) $u->pivot->conversion_value,
                    'price' => $u->pivot->price ? (float) $u->pivot->price : null,
                    'barcode' => $u->pivot->barcode,
                ])
            ),
            'images' => $this->whenLoaded('media', fn () =>
                $this->getMedia('product-images')->map(fn ($m) => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'name' => $m->name,
                    'size' => $m->size,
                ])
            ),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
