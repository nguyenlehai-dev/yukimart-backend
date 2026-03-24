<?php

namespace App\Modules\Core\Requests;

use App\Modules\Core\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organization = $this->route('organization');
        $organizationId = is_object($organization) ? $organization->id : $organization;

        return [
            'name' => 'sometimes|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('organizations', 'slug')->ignore($organizationId)],
            'description' => 'nullable|string',
            'status' => ['nullable', StatusEnum::rule()],
            'parent_id' => [
                'nullable',
                Rule::notIn([$organizationId]),
                Rule::when($this->filled('parent_id') && (int) $this->input('parent_id') !== 0, ['exists:organizations,id']),
            ],
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
