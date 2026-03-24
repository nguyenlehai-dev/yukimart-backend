<?php

namespace App\Modules\Document\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'so_ky_hieu' => $this->so_ky_hieu,
            'ten_van_ban' => $this->ten_van_ban,
            'noi_dung' => $this->noi_dung,
            'status' => $this->status,
            'ngay_ban_hanh' => $this->ngay_ban_hanh?->format('d/m/Y'),
            'ngay_xuat_ban' => $this->ngay_xuat_ban?->format('d/m/Y'),
            'ngay_hieu_luc' => $this->ngay_hieu_luc?->format('d/m/Y'),
            'ngay_het_hieu_luc' => $this->ngay_het_hieu_luc?->format('d/m/Y'),
            'issuing_agency' => $this->whenLoaded('issuingAgency', fn () => [
                'id' => $this->issuingAgency?->id,
                'name' => $this->issuingAgency?->name,
            ]),
            'issuing_level' => $this->whenLoaded('issuingLevel', fn () => [
                'id' => $this->issuingLevel?->id,
                'name' => $this->issuingLevel?->name,
            ]),
            'signer' => $this->whenLoaded('signer', fn () => [
                'id' => $this->signer?->id,
                'name' => $this->signer?->name,
            ]),
            'types' => $this->whenLoaded('types', fn () => $this->types->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
            ])->values()),
            'fields' => $this->whenLoaded('fields', fn () => $this->fields->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
            ])->values()),
            'attachments' => $this->whenLoaded('media', fn () => $this->attachments->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'url' => $media->getUrl(),
            ])->values()),
            'created_by' => $this->creator?->name ?? 'N/A',
            'updated_by' => $this->editor?->name ?? 'N/A',
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
