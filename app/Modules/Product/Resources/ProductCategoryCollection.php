<?php

namespace App\Modules\Product\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCategoryCollection extends ResourceCollection
{
    public $collects = ProductCategoryResource::class;
}
