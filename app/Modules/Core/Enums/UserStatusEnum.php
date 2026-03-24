<?php

namespace App\Modules\Core\Enums;

/**
 * Trạng thái người dùng.
 */
enum UserStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Banned = 'banned';

    /** Danh sách giá trị để validate. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Rule validation: in:active,inactive,banned */
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
            self::Banned => 'Bị khóa',
        };
    }
}
