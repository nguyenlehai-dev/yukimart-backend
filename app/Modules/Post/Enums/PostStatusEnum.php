<?php

namespace App\Modules\Post\Enums;

/**
 * Trạng thái bài viết.
 */
enum PostStatusEnum: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /** Danh sách giá trị để validate. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Rule validation: in:draft,published,archived */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    /** Nhãn tiếng Việt. */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bản nháp',
            self::Published => 'Đã xuất bản',
            self::Archived => 'Lưu trữ',
        };
    }
}
