<?php

namespace App\Modules\Post\Requests;

use App\Modules\Post\Enums\PostStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:posts,title',
            'content' => 'required|string|min:10',
            'status' => ['required', PostStatusEnum::rule()],
            'category_ids' => 'nullable|array|max:20',
            'category_ids.*' => 'integer|exists:post_categories,id',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,gif,webp|max:5120', // 5MB mỗi ảnh, tối đa 10 ảnh
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề không được để trống.',
            'title.string' => 'Tiêu đề phải là một chuỗi ký tự.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'title.unique' => 'Tiêu đề bài viết đã tồn tại.',
            'content.required' => 'Nội dung không được để trống.',
            'content.string' => 'Nội dung phải là một chuỗi ký tự.',
            'content.min' => 'Nội dung phải có ít nhất 10 ký tự.',
            'status.in' => 'Trạng thái không hợp lệ. Chỉ chấp nhận draft, published, archived.',
            'category_ids.*.exists' => 'Một hoặc nhiều danh mục không tồn tại.',
            'images.*.image' => 'File phải là hình ảnh.',
            'images.*.max' => 'Mỗi ảnh tối đa 5MB.',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
