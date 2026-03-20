<?php

namespace App\Modules\Auth\Requests;

use App\Modules\Auth\Services\RecaptchaService;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:1'],
        ];

        // Chỉ bắt buộc reCAPTCHA khi đã cấu hình
        if (RecaptchaService::isEnabled()) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'recaptcha_token.required' => 'Xác minh reCAPTCHA thất bại. Vui lòng thử lại.',
        ];
    }
}
