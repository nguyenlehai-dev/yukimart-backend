<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // min 8 ký tự, có ít nhất 1 chữ + 1 số.
            'password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
            ],
            'recaptcha_token' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Mật khẩu phải có cả chữ và số.',
            'password.min' => 'Mật khẩu phải tối thiểu 8 ký tự.',
        ];
    }
}
