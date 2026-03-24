<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'barcode', 'price', 'cost_price', 'name', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_variant_attributes',
            'variant_id',
            'attribute_value_id'
        );
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'variant_id');
    }
}
