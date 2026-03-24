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
        return [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'tax_code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'note' => 'nullable|string',
        ];
    }
}
