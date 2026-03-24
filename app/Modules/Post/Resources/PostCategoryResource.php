<?php

namespace App\Modules\Post\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'parent_id' => $this->parent_id,
            'depth' => $this->depth,
            'created_by' => $this->creator->name ?? 'N/A',
            'updated_by' => $this->editor->name ?? 'N/A',
            'created_at' => $this->created_at?->format('d/m/Y H:i:s'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i:s'),
            // Quan hệ khi load
            'parent' => $this->whenLoaded('parent', fn () => new PostCategoryResource($this->parent)),
            'children' => $this->whenLoaded('children', fn () => PostCategoryResource::collection($this->children)),
        ];
    }
}
