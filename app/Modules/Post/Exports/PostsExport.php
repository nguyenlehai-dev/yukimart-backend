<?php

namespace App\Modules\Post\Exports;

use App\Modules\Post\Models\Post;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PostsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * Xuất theo bộ lọc của index, đầy đủ trường như PostResource.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $posts = Post::with(['categories', 'creator', 'editor'])
            ->filter($this->filters)
            ->get();

        return $posts->map(fn ($post) => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => Str::slug($post->title),
            'content' => $post->content,
            'status' => $post->status,
            'view_count' => (int) $post->view_count,
            'categories' => $post->categories->pluck('name')->join(', ') ?: 'N/A',
            'created_by' => $post->creator?->name ?? 'N/A',
            'updated_by' => $post->editor?->name ?? 'N/A',
            'created_at' => $post->created_at?->format('d/m/Y H:i:s'),
            'updated_at' => $post->updated_at?->format('d/m/Y H:i:s'),
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Slug',
            'Content',
            'Status',
            'View Count',
            'Categories',
            'Created By',
            'Updated By',
            'Created At',
            'Updated At',
        ];
    }
}
