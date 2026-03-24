<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PriceList extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
        'auto_update_from_base' => 'boolean',
        'add_products_from_base' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'formula_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name).'-'.Str::random(4);
            }
            $model->created_by = $model->created_by ?? auth()->id();
            $model->updated_by = $model->updated_by ?? auth()->id();
        });

        static::updating(function (self $model) {
            $model->updated_by = auth()->id();
        });
    }

    // ── Relationships ──

    public function basePriceList()
    {
        return $this->belongsTo(self::class, 'base_price_list_id');
    }

    public function items()
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function organizations()
    {
        return $this->belongsToMany(
            \App\Modules\Core\Models\Organization::class,
            'price_list_organizations'
        );
    }

    public function creator()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'created_by');
    }

    // ── Scopes ──

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(isset($filters['is_active_now']), function ($q) {
                $now = now();
                $q->where('status', 'active')
                    ->where(function ($q2) use ($now) {
                        $q2->whereNull('start_date')->orWhere('start_date', '<=', $now);
                    })
                    ->where(function ($q2) use ($now) {
                        $q2->whereNull('end_date')->orWhere('end_date', '>=', $now);
                    });
            })
            ->when($filters['sort_by'] ?? null, function ($q, $sortBy) use ($filters) {
                $q->orderBy($sortBy, $filters['sort_order'] ?? 'desc');
            }, function ($q) {
                $q->orderByDesc('id');
            });
    }

    // ── Helpers ──

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Tính giá theo công thức từ giá gốc.
     */
    public function calculatePrice(float $basePrice, ?string $formulaType = null, ?float $formulaValue = null): float
    {
        $type = $formulaType ?? $this->formula_type;
        $value = $formulaValue ?? $this->formula_value;

        if (! $type || $value === null) {
            return $basePrice;
        }

        $newPrice = match ($type) {
            'percentage' => $basePrice * (1 + $value / 100),
            'fixed_amount' => $basePrice + $value,
            default => $basePrice,
        };

        return $this->applyRounding(max(0, $newPrice));
    }

    /**
     * Làm tròn giá.
     */
    public function applyRounding(float $price): float
    {
        if (! $this->rounding_type || $this->rounding_type === 'none') {
            return $price;
        }

        $precision = match ($this->rounding_type) {
            'unit' => 1,
            'ten' => 10,
            'hundred' => 100,
            'thousand' => 1000,
            'ten_thousand' => 10000,
            default => 1,
        };

        $method = $this->rounding_method ?? 'round';

        return match ($method) {
            'ceil' => ceil($price / $precision) * $precision,
            'floor' => floor($price / $precision) * $precision,
            default => round($price / $precision) * $precision,
        };
    }
}
