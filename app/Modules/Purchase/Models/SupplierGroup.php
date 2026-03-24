<?php

namespace App\Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierGroup extends Model
{
    protected $guarded = ['id'];

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'group_id');
    }
}
