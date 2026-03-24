<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'product_id', 'variant_id', 'organization_id', 'quantity', 'cost_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'cost_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Core\Models\Organization::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
