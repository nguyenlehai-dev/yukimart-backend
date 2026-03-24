<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Core\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class StockCheck extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_deviation_amount' => 'decimal:2',
        'check_date' => 'datetime',
        'balanced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->code)) {
                $m->code = 'KK'.str_pad((self::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
            }
            $m->created_by = $m->created_by ?? auth()->id();
            $m->updated_by = $m->updated_by ?? auth()->id();
            $m->check_date = $m->check_date ?? now();
        });
        static::updating(fn (self $m) => $m->updated_by = auth()->id());
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function items()
    {
        return $this->hasMany(StockCheckItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'created_by');
    }

    public function scopeFilter($query, array $f)
    {
        return $query
            ->when($f['search'] ?? null, fn ($q, $s) => $q->where('code', 'like', "%{$s}%"))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['organization_id'] ?? null, fn ($q, $v) => $q->where('organization_id', $v))
            ->when($f['from_date'] ?? null, fn ($q, $v) => $q->whereDate('check_date', '>=', $v))
            ->when($f['to_date'] ?? null, fn ($q, $v) => $q->whereDate('check_date', '<=', $v))
            ->when($f['sort_by'] ?? null, fn ($q, $s) => $q->orderBy($s, $f['sort_order'] ?? 'desc'), fn ($q) => $q->orderByDesc('id'));
    }
}
