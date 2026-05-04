<?php

namespace App\Modules\ShopProduct\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Đọc Excel/CSV thành mảng row có header gốc (chưa normalize).
 *
 * Khác với WithHeadingRow của Maatwebsite — class này giữ nguyên tên cột tiếng Việt
 * (vd: "Tên sản phẩm", "Giá bán") để FE hiển thị đúng và auto-detect chạy trên bản
 * normalize riêng. Dòng đầu tiên có ít nhất 1 ô không rỗng được coi là header.
 */
class ShopProductImport implements ToArray
{
    /** @var array<int, array<string, mixed>> Mỗi row: ['Header gốc' => giá trị, ...] */
    public array $rows = [];

    /** @var array<int, string> Header gốc (chưa normalize) */
    public array $headings = [];

    public function array(array $array): void
    {
        // Tìm dòng header: trong 10 dòng đầu, lấy dòng có nhiều ô không rỗng nhất
        // (>= 2 ô) — bỏ qua title row 1 ô. Nếu không thấy thì rơi về dòng đầu tiên có dữ liệu.
        $headerIdx = null;
        $bestNonEmpty = 0;
        $scanLimit = min(10, \count($array));
        for ($i = 0; $i < $scanLimit; $i++) {
            $row = $array[$i] ?? null;
            if (! \is_array($row)) {
                continue;
            }
            $nonEmpty = 0;
            foreach ($row as $cell) {
                if ($cell !== null && trim((string) $cell) !== '') {
                    $nonEmpty++;
                }
            }
            if ($nonEmpty >= 2 && $nonEmpty > $bestNonEmpty) {
                $bestNonEmpty = $nonEmpty;
                $headerIdx = $i;
            }
        }
        if ($headerIdx === null) {
            // Fallback: dòng đầu tiên có ít nhất 1 ô.
            foreach ($array as $i => $row) {
                if (! \is_array($row)) {
                    continue;
                }
                foreach ($row as $cell) {
                    if ($cell !== null && trim((string) $cell) !== '') {
                        $headerIdx = $i;
                        break 2;
                    }
                }
            }
        }
        if ($headerIdx === null) {
            $this->headings = [];
            $this->rows = [];

            return;
        }

        // Header: chuyển tất cả về string, bỏ rỗng cuối, đảm bảo unique (nếu trùng thêm hậu tố).
        $rawHeader = $array[$headerIdx];
        $headings = [];
        $seen = [];
        foreach ($rawHeader as $idx => $cell) {
            $name = trim((string) ($cell ?? ''));
            if ($name === '') {
                $name = "Cột {$idx}";
            }
            $base = $name;
            $n = 2;
            while (isset($seen[$name])) {
                $name = $base.' ('.$n.')';
                $n++;
            }
            $seen[$name] = true;
            $headings[$idx] = $name;
        }
        $this->headings = array_values($headings);

        // Map các row còn lại theo header.
        $rows = [];
        foreach ($array as $i => $row) {
            if ($i <= $headerIdx) {
                continue;
            }
            if (! \is_array($row)) {
                continue;
            }
            // Bỏ qua dòng hoàn toàn rỗng.
            $allEmpty = true;
            foreach ($row as $cell) {
                if ($cell !== null && trim((string) $cell) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }

            $assoc = [];
            foreach ($headings as $colIdx => $headerName) {
                $assoc[$headerName] = $row[$colIdx] ?? null;
            }
            $rows[] = $assoc;
        }
        $this->rows = $rows;
    }
}
