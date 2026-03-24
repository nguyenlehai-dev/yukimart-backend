<?php

namespace App\Modules\Document\Requests;

use App\Modules\Document\Enums\DocumentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $document = $this->route('document');

        return [
            'so_ky_hieu' => ['sometimes', 'string', 'max:255', Rule::unique('documents', 'so_ky_hieu')->ignore($document)],
            'ten_van_ban' => 'sometimes|string|max:255',
            'noi_dung' => 'nullable|string',
            'issuing_agency_id' => 'nullable|integer|exists:document_issuing_agencies,id',
            'issuing_level_id' => 'nullable|integer|exists:document_issuing_levels,id',
            'signer_id' => 'nullable|integer|exists:document_signers,id',
            'document_type_ids' => 'nullable|array|max:50',
            'document_type_ids.*' => 'integer|exists:document_types,id',
            'document_field_ids' => 'nullable|array|max:50',
            'document_field_ids.*' => 'integer|exists:document_fields,id',
            'ngay_ban_hanh' => 'nullable|date',
            'ngay_xuat_ban' => 'nullable|date',
            'ngay_hieu_luc' => 'nullable|date',
            'ngay_het_hieu_luc' => 'nullable|date|after_or_equal:ngay_hieu_luc',
            'status' => ['sometimes', DocumentStatusEnum::rule()],
            'attachments' => 'nullable|array|max:20',
            'attachments.*' => 'file|max:10240',
            'remove_attachment_ids' => 'nullable|array',
            'remove_attachment_ids.*' => 'integer|exists:media,id',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
