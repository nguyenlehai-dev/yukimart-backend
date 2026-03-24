<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'status',
        'created_by', 'updated_by',
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

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
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
            $q->where('name', 'like', '%'.$search.'%');
        })->when($filters['status'] ?? null, function ($q, $status) {
            $q->where('status', $status);
        })->when($filters['sort_by'] ?? 'id', function ($q, $sortBy) use ($filters) {
            $allowed = ['id', 'name', 'created_at'];
            $column = in_array($sortBy, $allowed) ? $sortBy : 'id';
            $q->orderBy($column, $filters['sort_order'] ?? 'desc');
        });

        return $query;
    }
}
