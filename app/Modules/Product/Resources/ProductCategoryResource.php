<?php

namespace App\Modules\Product\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order,
            'children' => self::collection($this->whenLoaded('children')),
            'products_count' => $this->whenCounted('products'),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
