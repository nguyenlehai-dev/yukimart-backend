<?php

namespace App\Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'debt' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->code)) {
                $m->code = 'NCC'.str_pad((self::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
            }
            $m->created_by = $m->created_by ?? auth()->id();
            $m->updated_by = $m->updated_by ?? auth()->id();
        });
        static::updating(fn (self $m) => $m->updated_by = auth()->id());
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function scopeFilter($query, array $f)
    {
        return $query
            ->when($f['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['sort_by'] ?? null, fn ($q, $s) => $q->orderBy($s, $f['sort_order'] ?? 'desc'), fn ($q) => $q->orderByDesc('id'));
    }
}
