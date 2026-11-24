<?php

namespace App\Exports;

use Generator;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Export Data Service dealer ke .xlsx (filter sama dengan tabel di halaman).
 * Semua kolom teks disimpan sebagai teks (VIN tidak berubah jadi angka); Tanggal Service = tanggal Excel (mm-dd-yyyy).
 */
class DealerServiceDataExport extends DefaultValueBinder implements
    FromGenerator, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting, WithColumnWidths, WithEvents, WithTitle
{
    public function __construct(private Builder $query)
    {
    }

    public function generator(): Generator
    {
        // 1 query + cursor: jauh lebih cepat dari FromQuery (chunk + OFFSET berulang pada puluhan ribu baris)
        foreach ($this->query->cursor() as $row) {
            yield $row;
        }
    }

    public function title(): string
    {
        return 'Service Data';
    }

    public function headings(): array
    {
        return ['No. Polisi', 'VIN', 'Model', 'Tanggal Service', 'Category Service', 'Category Payment', 'Customer Request'];
    }

    public function map($row): array
    {
        return [
            $row->nomor_polisi,
            $row->no_vin,
            $row->nm_model,
            // Hanya bagian tanggal (tanpa konversi timezone) agar tanggal di Excel tidak mundur 1 hari
            $row->tanggal_faktur ? ExcelDate::stringToExcel(substr($row->tanggal_faktur, 0, 10)) : null,
            $row->nm_category_vehicles,
            $row->nm_category_2_vehicles,
            $row->customer_request,
        ];
    }

    /**
     * Kolom D (tanggal) tetap angka; kolom lain selalu teks.
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getRow() > 1 && $cell->getColumn() === 'D' && is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);

            return true;
        }

        if ($value !== null && $value !== '') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return ['D' => 'mm-dd-yyyy'];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 22, 'C' => 22, 'D' => 16, 'E' => 24, 'F' => 24, 'G' => 70];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Header gaya Mazda: grafit + garis bawah Soul Red
                $header = $sheet->getStyle('A1:G1');
                $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1B2027');
                $header->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $header->getBorders()->getBottom()->setBorderStyle('medium')->getColor()->setRGB('A6192E');
                $sheet->getRowDimension(1)->setRowHeight(22);

                $sheet->getStyle("D1:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:G{$lastRow}");
            },
        ];
    }
}
