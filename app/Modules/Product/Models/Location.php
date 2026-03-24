<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'organization_id', 'status',
        'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_by = $model->updated_by = auth()->id();
        });

        static::updating(function (self $model) {
            $model->updated_by = auth()->id();
        });
    }

    public function organization()
    {
        return $this->belongsTo(\App\Modules\Core\Models\Organization::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_location');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class, 'updated_by');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('name', 'like', '%'.$search.'%');
        })->when($filters['status'] ?? null, function ($q, $status) {
            $q->where('status', $status);
        })->when($filters['organization_id'] ?? null, function ($q, $orgId) {
            $q->where('organization_id', $orgId);
        })->when($filters['sort_by'] ?? 'id', function ($q, $sortBy) use ($filters) {
            $allowed = ['id', 'name', 'created_at'];
            $column = in_array($sortBy, $allowed) ? $sortBy : 'id';
            $q->orderBy($column, $filters['sort_order'] ?? 'desc');
        });

        return $query;
    }
}
