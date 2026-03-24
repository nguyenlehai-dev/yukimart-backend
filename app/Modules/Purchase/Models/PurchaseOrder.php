<?php

namespace App\Modules\Purchase\Models;

use App\Modules\Core\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'debt_amount' => 'decimal:2',
        'order_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->code)) {
                $m->code = 'NH'.str_pad((self::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
            }
            $m->created_by = $m->created_by ?? auth()->id();
            $m->updated_by = $m->updated_by ?? auth()->id();
            $m->order_date = $m->order_date ?? now();
        });
        static::updating(fn (self $m) => $m->updated_by = auth()->id());
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function scopeFilter($query, array $f)
    {
        return $query
            ->when($f['search'] ?? null, fn ($q, $s) => $q->where('code', 'like', "%{$s}%"))
            ->when($f['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['organization_id'] ?? null, fn ($q, $v) => $q->where('organization_id', $v))
            ->when($f['from_date'] ?? null, fn ($q, $v) => $q->whereDate('order_date', '>=', $v))
            ->when($f['to_date'] ?? null, fn ($q, $v) => $q->whereDate('order_date', '<=', $v))
            ->when($f['sort_by'] ?? null, fn ($q, $s) => $q->orderBy($s, $f['sort_order'] ?? 'desc'), fn ($q) => $q->orderByDesc('id'));
    }
}
