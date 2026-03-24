<?php

namespace App\Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierDebtTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'debt_before' => 'decimal:2',
        'debt_after' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->code)) {
                $prefix = match ($m->type) {
                    'payment' => 'PC',
                    'discount' => 'CK',
                    'adjustment' => 'DC',
                    default => 'TX',
                };
                $m->code = $prefix . str_pad((self::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
            }
            $m->created_by = $m->created_by ?? auth()->id();
            $m->transaction_date = $m->transaction_date ?? now();
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'created_by');
    }

    public function scopeFilter($query, array $f)
    {
        return $query
            ->when($f['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($f['from_date'] ?? null, fn ($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($f['to_date'] ?? null, fn ($q, $v) => $q->whereDate('transaction_date', '<=', $v))
            ->orderByDesc('id');
    }
}
