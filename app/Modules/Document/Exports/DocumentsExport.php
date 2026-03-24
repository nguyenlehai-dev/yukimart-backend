<?php

namespace App\Modules\Document\Exports;

use App\Modules\Document\Models\Document;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DocumentsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection()
    {
        $documents = Document::with([
            'issuingAgency',
            'issuingLevel',
            'signer',
            'types',
            'fields',
            'creator',
            'editor',
        ])->filter($this->filters)->get();

        return $documents->map(fn ($item) => [
            'id' => $item->id,
            'so_ky_hieu' => $item->so_ky_hieu,
            'ten_van_ban' => $item->ten_van_ban,
            'noi_dung' => $item->noi_dung,
            'loai_van_ban' => $item->types->pluck('name')->join(', '),
            'linh_vuc' => $item->fields->pluck('name')->join(', '),
            'co_quan_ban_hanh' => $item->issuingAgency?->name,
            'cap_ban_hanh' => $item->issuingLevel?->name,
            'nguoi_ky' => $item->signer?->name,
            'ngay_ban_hanh' => $item->ngay_ban_hanh?->format('d/m/Y'),
            'ngay_xuat_ban' => $item->ngay_xuat_ban?->format('d/m/Y'),
            'ngay_hieu_luc' => $item->ngay_hieu_luc?->format('d/m/Y'),
            'ngay_het_hieu_luc' => $item->ngay_het_hieu_luc?->format('d/m/Y'),
            'status' => $item->status,
            'created_by' => $item->creator?->name ?? 'N/A',
            'updated_by' => $item->editor?->name ?? 'N/A',
            'created_at' => $item->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $item->updated_at?->format('H:i:s d/m/Y'),
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Số ký hiệu',
            'Tên văn bản',
            'Nội dung',
            'Loại văn bản',
            'Lĩnh vực',
            'Cơ quan ban hành',
            'Cấp ban hành',
            'Người ký',
            'Ngày ban hành',
            'Ngày xuất bản',
            'Ngày hiệu lực',
            'Ngày hết hiệu lực',
            'Trạng thái',
            'Created By',
            'Updated By',
            'Created At',
            'Updated At',
        ];
    }
}
