<?php

namespace App\Modules\Document\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'so_ky_hieu',
        'ten_van_ban',
        'noi_dung',
        'issuing_agency_id',
        'issuing_level_id',
        'signer_id',
        'ngay_ban_hanh',
        'ngay_xuat_ban',
        'ngay_hieu_luc',
        'ngay_het_hieu_luc',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ngay_ban_hanh' => 'date',
        'ngay_xuat_ban' => 'date',
        'ngay_hieu_luc' => 'date',
        'ngay_het_hieu_luc' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function (Document $document) {
            $document->created_by = auth()->id();
            $document->updated_by = auth()->id();
        });

        static::updating(function (Document $document) {
            $document->updated_by = auth()->id();
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function issuingAgency()
    {
        return $this->belongsTo(IssuingAgency::class, 'issuing_agency_id');
    }

    public function issuingLevel()
    {
        return $this->belongsTo(IssuingLevel::class, 'issuing_level_id');
    }

    public function signer()
    {
        return $this->belongsTo(DocumentSigner::class, 'signer_id');
    }

    public function types()
    {
        return $this->belongsToMany(DocumentType::class, 'document_document_type')
            ->withTimestamps();
    }

    public function fields()
    {
        return $this->belongsToMany(DocumentField::class, 'document_document_field')
            ->withTimestamps();
    }

    public function attachments()
    {
        return $this->media()->where('collection_name', 'document-attachments')->orderBy('order_column');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document-attachments');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('ten_van_ban', 'like', '%'.$search.'%')
                    ->orWhere('so_ky_hieu', 'like', '%'.$search.'%');
            });
        })->when($filters['status'] ?? null, function ($q, $status) {
            $q->where('status', $status);
        })->when($filters['document_type_id'] ?? null, function ($q, $documentTypeId) {
            $q->whereHas('types', fn ($typeQuery) => $typeQuery->where('document_types.id', $documentTypeId));
        })->when($filters['document_field_id'] ?? null, function ($q, $documentFieldId) {
            $q->whereHas('fields', fn ($fieldQuery) => $fieldQuery->where('document_fields.id', $documentFieldId));
        })->when($filters['issuing_agency_id'] ?? null, fn ($q, $id) => $q->where('issuing_agency_id', $id))
            ->when($filters['issuing_level_id'] ?? null, fn ($q, $id) => $q->where('issuing_level_id', $id))
            ->when($filters['signer_id'] ?? null, fn ($q, $id) => $q->where('signer_id', $id))
            ->when($filters['from_date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['sort_by'] ?? 'created_at', function ($q, $sortBy) use ($filters) {
                $allowed = ['id', 'so_ky_hieu', 'ten_van_ban', 'ngay_ban_hanh', 'created_at', 'updated_at'];
                $column = in_array($sortBy, $allowed) ? $sortBy : 'created_at';
                $q->orderBy($column, $filters['sort_order'] ?? 'desc');
            });
    }
}
