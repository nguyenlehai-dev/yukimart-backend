<?php

namespace App\Modules\ShopProduct\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shop_products';

    protected $fillable = [
        'sku', 'barcode', 'name', 'slug', 'product_type', 'category_path', 'category', 'brand',
        'original_price', 'sale_price', 'wholesale_price', 'cost', 'stock', 'reserved', 'threshold', 'unit', 'status',
        'description', 'image_urls', 'is_hot_deal', 'is_suggested', 'warehouse',
        'weight', 'points', 'extra',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'stock' => 'integer',
        'reserved' => 'integer',
        'threshold' => 'integer',
        'is_hot_deal' => 'boolean',
        'is_suggested' => 'boolean',
        'weight' => 'decimal:3',
        'points' => 'integer',
        'extra' => 'array',
    ];
}
