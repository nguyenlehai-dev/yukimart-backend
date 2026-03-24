<?php

namespace App\Modules\Post\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => Str::slug($this->title),
            'content' => $this->content,
            'status' => $this->status,
            'view_count' => (int) $this->view_count,
            'categories' => $this->whenLoaded('categories', fn () => PostCategoryResource::collection($this->categories)),
            'attachments' => $this->whenLoaded('media', function () {
                return $this->media
                    ->where('collection_name', 'post-attachments')
                    ->sortBy('order_column')
                    ->values()
                    ->map(fn (Media $media) => [
                        'id' => $media->id,
                        'url' => $media->getFullUrl(),
                        'original_name' => $media->getCustomProperty('original_name') ?: $media->file_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                        'sort_order' => $media->order_column,
                    ]);
            }),
            'created_by' => $this->creator->name ?? 'N/A',
            'updated_by' => $this->editor->name ?? 'N/A',
            'created_at' => $this->created_at?->format('d/m/Y H:i:s'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i:s'),
        ];
    }
}
