<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = ['name', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id')->orderBy('sort_order');
    }
}
