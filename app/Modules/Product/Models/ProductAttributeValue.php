<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    protected $fillable = ['attribute_id', 'value', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }
}
