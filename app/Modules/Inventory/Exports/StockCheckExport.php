<?php

namespace App\Modules\Inventory\Exports;

use App\Modules\Inventory\Models\StockCheck;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockCheckExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        return StockCheck::with(['organization:id,name', 'creator:id,name'])->filter($this->filters);
    }

    public function headings(): array
    {
        return ['Ma phieu', 'Chi nhanh', 'SL Tang', 'SL Giam', 'Gia tri chenh lech', 'Trang thai', 'Ngay kiem', 'Nguoi tao'];
    }

    public function map($c): array
    {
        return [$c->code, $c->organization?->name, $c->total_increase, $c->total_decrease, $c->total_deviation_amount, $c->status, $c->check_date?->format('d/m/Y H:i'), $c->creator?->name ?? ''];
    }
}
