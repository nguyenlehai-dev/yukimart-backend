<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Resource cho API tree permission (cấu trúc cây parent_id). */
class PermissionTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'parent_id' => $this->parent_id,
            'children' => $this->whenLoaded(
                'children',
                fn () => PermissionTreeResource::collection($this->children),
                []
            ),
        ];
    }
}
