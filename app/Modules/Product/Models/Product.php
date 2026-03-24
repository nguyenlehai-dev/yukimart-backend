<?php

namespace App\Modules\Product\Models;

use App\Modules\Product\Enums\ProductStatusEnum;
use App\Modules\Product\Enums\ProductTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'code', 'barcode', 'name', 'slug', 'description', 'type',
        'category_id', 'brand_id', 'base_unit_id',
        'base_price', 'cost_price', 'weight',
        'allow_negative_stock', 'min_stock', 'max_stock',
        'status', 'is_active', 'point',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'allow_negative_stock' => 'boolean',
        'is_active' => 'boolean',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
        'point' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_by = $model->updated_by = auth()->id();
            if (empty($model->slug)) {
                $model->slug = self::generateUniqueSlug(Str::slug($model->name));
            }
            if (empty($model->code)) {
                $model->code = self::generateCode($model->type);
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

    public static function generateCode(string $type = 'product'): string
    {
        $prefix = match ($type) {
            'service' => 'DV',
            'combo' => 'CB',
            'manufacturing' => 'SX',
            default => 'SP',
        };

        $latest = static::where('code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('code');

        $number = 1;
        if ($latest) {
            $number = (int) substr($latest, strlen($prefix)) + 1;
        }

        return $prefix.str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product-images');
    }

    // ── Relationships ──

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function baseUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'base_unit_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function components()
    {
        return $this->hasMany(ProductComponent::class, 'parent_product_id');
    }

    public function usedInProducts()
    {
        return $this->hasMany(ProductComponent::class, 'component_product_id');
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'product_location');
    }

    public function unitConversions()
    {
        return $this->belongsToMany(ProductUnit::class, 'product_product_unit')
            ->withPivot('conversion_value', 'price', 'barcode');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'updated_by');
    }

    // ── Scopes ──

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%');
            });
        })->when($filters['type'] ?? null, function ($q, $type) {
            $q->where('type', $type);
        })->when($filters['category_id'] ?? null, function ($q, $categoryId) {
            $q->where('category_id', $categoryId);
        })->when($filters['brand_id'] ?? null, function ($q, $brandId) {
            $q->where('brand_id', $brandId);
        })->when(isset($filters['is_active']), function ($q) use ($filters) {
            $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        })->when($filters['status'] ?? null, function ($q, $status) {
            $q->where('status', $status);
        })->when($filters['sort_by'] ?? 'id', function ($q, $sortBy) use ($filters) {
            $allowed = ['id', 'name', 'code', 'base_price', 'cost_price', 'created_at', 'updated_at'];
            $column = in_array($sortBy, $allowed) ? $sortBy : 'id';
            $q->orderBy($column, $filters['sort_order'] ?? 'desc');
        });

        return $query;
    }
}
