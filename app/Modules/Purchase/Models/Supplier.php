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

    // ── Relationships ──

    public function group()
    {
        return $this->belongsTo(SupplierGroup::class, 'group_id');
    }

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Core\Models\Organization::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function debtTransactions()
    {
        return $this->hasMany(SupplierDebtTransaction::class);
    }

    // ── Scopes ──

    public function scopeFilter($query, array $f)
    {
        return $query
            ->when($f['search'] ?? null, fn ($q, $s) => $q->where(function ($q2) use ($s) {
                $q2->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('company', 'like', "%{$s}%");
            }))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['group_id'] ?? null, fn ($q, $v) => $q->where('group_id', $v))
            ->when($f['organization_id'] ?? null, fn ($q, $v) => $q->where('organization_id', $v))
            ->when(isset($f['has_debt']), fn ($q) => $q->where('debt', '>', 0))
            ->when($f['sort_by'] ?? null, fn ($q, $s) => $q->orderBy($s, $f['sort_order'] ?? 'desc'), fn ($q) => $q->orderByDesc('id'));
    }
}
