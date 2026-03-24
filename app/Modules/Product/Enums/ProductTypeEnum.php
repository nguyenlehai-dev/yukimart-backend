<?php

namespace App\Modules\Product\Enums;

enum ProductTypeEnum: string
{
    case Product = 'product';
    case Service = 'service';
    case Combo = 'combo';
    case Manufacturing = 'manufacturing';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Hàng hóa',
            self::Service => 'Dịch vụ',
            self::Combo => 'Combo – Đóng gói',
            self::Manufacturing => 'Hàng sản xuất',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
