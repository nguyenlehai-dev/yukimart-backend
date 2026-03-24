<?php

namespace App\Modules\Purchase\Models;

use App\Modules\Core\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'supplier_paid' => 'decimal:2',
        'debt_amount' => 'decimal:2',
        'return_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->code)) {
                $m->code = 'TH'.str_pad((self::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
            }
            $m->created_by = $m->created_by ?? auth()->id();
            $m->updated_by = $m->updated_by ?? auth()->id();
            $m->return_date = $m->return_date ?? now();
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

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'created_by');
    }

    public function scopeFilter($query, array $f)
    {
        return $query
            ->when($f['search'] ?? null, fn ($q, $s) => $q->where('code', 'like', "%{$s}%"))
            ->when($f['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['organization_id'] ?? null, fn ($q, $v) => $q->where('organization_id', $v))
            ->when($f['purchase_order_id'] ?? null, fn ($q, $v) => $q->where('purchase_order_id', $v))
            ->when($f['from_date'] ?? null, fn ($q, $v) => $q->whereDate('return_date', '>=', $v))
            ->when($f['to_date'] ?? null, fn ($q, $v) => $q->whereDate('return_date', '<=', $v))
            ->when($f['sort_by'] ?? null, fn ($q, $s) => $q->orderBy($s, $f['sort_order'] ?? 'desc'), fn ($q) => $q->orderByDesc('id'));
    }
}
