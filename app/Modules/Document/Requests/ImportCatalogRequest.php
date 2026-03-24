<?php

namespace App\Modules\Document\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'file' => [
                'description' => 'File Excel để import (xlsx, xls, csv).',
            ],
        ];
    }
}
