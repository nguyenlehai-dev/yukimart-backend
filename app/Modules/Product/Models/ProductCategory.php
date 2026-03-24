<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'status',
        'parent_id', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_by = $model->updated_by = auth()->id();
            if (empty($model->slug)) {
                $model->slug = self::generateUniqueSlug(Str::slug($model->name));
            }
        });

        static::updating(function (self $model) {
            $model->updated_by = auth()->id();
            if ($model->isDirty('name') && ! $model->isDirty('slug')) {
                $model->slug = self::generateUniqueSlug(Str::slug($model->name), $model->id);
            }
        });
    }

    public static function generateUniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $original = $slug;
        $count = 1;
        $query = static::where('slug', $slug);
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
        while ($query->exists()) {
            $slug = $original.'-'.$count++;
            $query = static::where('slug', $slug);
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
        }

        return $slug;
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'updated_by');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        })->when($filters['status'] ?? null, function ($q, $status) {
            $q->where('status', $status);
        })->when($filters['parent_id'] ?? null, function ($q, $parentId) {
            $q->where('parent_id', $parentId);
        })->when($filters['sort_by'] ?? 'id', function ($q, $sortBy) use ($filters) {
            $allowed = ['id', 'name', 'sort_order', 'created_at'];
            $column = in_array($sortBy, $allowed) ? $sortBy : 'id';
            $q->orderBy($column, $filters['sort_order'] ?? 'desc');
        });

        return $query;
    }

    public function scopeTreeOrder($query)
    {
        return $query->orderByRaw('COALESCE(parent_id, 0), sort_order, id');
    }
}
