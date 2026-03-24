<?php

namespace App\Modules\Core\Enums;

/**
 * Trạng thái chung: active, inactive (Organization, PostCategory, ...).
 */
enum StatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /** Danh sách giá trị để validate. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Rule validation: in:active,inactive */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    /** Nhãn tiếng Việt. */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang hoạt động',
            self::Inactive => 'Không hoạt động',
        };
    }
}
