<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductUnit;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class StockCheckItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'actual_quantity' => 'decimal:2',
        'deviation' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'deviation_amount' => 'decimal:2',
    ];

    public function stockCheck()
    {
        return $this->belongsTo(StockCheck::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }
}
