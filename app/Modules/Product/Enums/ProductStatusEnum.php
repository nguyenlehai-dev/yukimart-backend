<?php

namespace App\Modules\Product\Enums;

enum ProductStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Discontinued = 'discontinued';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang kinh doanh',
            self::Inactive => 'Ngừng kinh doanh',
            self::Discontinued => 'Ngừng sản xuất',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
