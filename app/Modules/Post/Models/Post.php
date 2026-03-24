<?php

namespace App\Modules\Post\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected static function newFactory()
    {
        return \Database\Factories\PostFactory::new();
    }

    protected $fillable = [
        'title',
        'content',
        'status',
        'view_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'view_count' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(fn ($post) => $post->created_by = $post->updated_by = auth()->id());
        static::updating(fn ($post) => $post->updated_by = auth()->id());
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Danh mục của bài viết (quan hệ nhiều-nhiều qua bảng pivot). */
    public function categories()
    {
        return $this->belongsToMany(PostCategory::class, 'post_post_category', 'post_id', 'post_category_id')
            ->withTimestamps();
    }

    public function attachments()
    {
        return $this->media()->where('collection_name', 'post-attachments')->orderBy('order_column');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('post-attachments');
    }

    public function registerMediaConversions(?Media $media = null): void {}

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['category_id'] ?? null, function ($query, $categoryId) {
            $query->whereHas('categories', fn ($q) => $q->where('post_categories.id', $categoryId));
        })->when($filters['sort_by'] ?? 'created_at', function ($query, $sortBy) use ($filters) {
            $allowed = ['id', 'title', 'created_at', 'view_count'];
            $column = in_array($sortBy, $allowed) ? $sortBy : 'created_at';
            $query->orderBy($column, $filters['sort_order'] ?? 'desc');
        });
    }
}
