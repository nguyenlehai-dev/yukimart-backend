<?php

namespace App\Modules\ShopProduct\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopEntry extends Model
{
    use SoftDeletes;

    protected $table = 'shop_entries';

    protected $fillable = ['entity', 'data', 'search_text'];

    protected $casts = [
        'data' => 'array',
    ];
}
