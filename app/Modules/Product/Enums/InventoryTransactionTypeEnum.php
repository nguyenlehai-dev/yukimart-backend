<?php

namespace App\Modules\Product\Enums;

enum InventoryTransactionTypeEnum: string
{
    case Import = 'import';
    case Export = 'export';
    case Sale = 'sale';
    case Return = 'return';
    case Adjust = 'adjust';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Import => 'Nhập hàng',
            self::Export => 'Xuất hàng',
            self::Sale => 'Bán hàng',
            self::Return => 'Trả hàng',
            self::Adjust => 'Kiểm kho',
            self::Transfer => 'Chuyển kho',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
