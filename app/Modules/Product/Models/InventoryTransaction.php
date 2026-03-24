<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'inventory_id', 'type', 'quantity_change', 'quantity_after',
        'cost_price', 'reference_type', 'reference_id', 'note', 'created_by',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'cost_price' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_by = $model->created_by ?? auth()->id();
            $model->created_at = $model->created_at ?? now();
        });
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'created_by');
    }
}
