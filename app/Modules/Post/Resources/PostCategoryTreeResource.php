<?php

namespace App\Modules\Post\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource dùng cho API tree: chỉ có id, name, slug, ... và children (đệ quy).
 * Không có parent để tránh tham chiếu vòng → "Maximum stack depth exceeded".
 */
class PostCategoryTreeResource extends JsonResource
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
            'children' => $this->whenLoaded(
                'children',
                fn () => PostCategoryTreeResource::collection($this->children),
                []
            ),
        ];
    }
}
