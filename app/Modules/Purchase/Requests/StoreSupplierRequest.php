<?php

namespace App\Modules\Purchase\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => ['nullable', 'string', 'max:20', $supplierId ? "unique:suppliers,phone,{$supplierId}" : 'unique:suppliers,phone'],
            'email' => 'nullable|email|max:255',
            'tax_code' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'group_id' => 'nullable|exists:supplier_groups,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'note' => 'nullable|string',
        ];
    }
}
