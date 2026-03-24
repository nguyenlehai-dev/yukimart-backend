<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductComponent extends Model
{
    protected $fillable = [
        'parent_product_id', 'component_product_id', 'quantity', 'unit_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function componentProduct()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }
}
