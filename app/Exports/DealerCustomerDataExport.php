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
 * Export Customer Data dealer ke .xlsx (filter sama dengan tabel di halaman).
 * Teks (telepon, VIN, engine, no. polisi) disimpan sebagai teks agar angka 0 di depan tidak hilang
 * dan VIN tidak berubah jadi format angka ilmiah. Tanggal = tanggal Excel (mm-dd-yyyy), odometer = angka.
 */
class DealerCustomerDataExport extends DefaultValueBinder implements
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
        return 'Customer Data';
    }

    public function headings(): array
    {
        return [
            'No. Polisi', 'Type Customer', 'Nama Customer', 'Telephone 1', 'Telephone 2', 'Email',
            'VIN', 'Engine', 'Last Service Date', 'Odometer', 'Nama Pemakai', 'Alamat',
        ];
    }

    public function map($row): array
    {
        return [
            $row->nomor_polisi,
            $row->type_customer,
            $row->nama_customer,
            $row->telephone_1,
            $row->telephone_2,
            $row->email,
            $row->no_vin,
            $row->no_mesin,
            // Hanya bagian tanggal (tanpa konversi timezone) agar tanggal di Excel tidak mundur 1 hari
            $row->tanggal_last_service ? ExcelDate::stringToExcel(substr($row->tanggal_last_service, 0, 10)) : null,
            $row->last_odo_meter !== null ? (float) $row->last_odo_meter : null,
            $row->nm_pemakai,
            $row->alamat,
        ];
    }

    /**
     * Kolom I (tanggal) & J (odometer) tetap angka; kolom lain selalu teks.
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getRow() > 1 && in_array($cell->getColumn(), ['I', 'J'], true) && is_numeric($value)) {
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
        return [
            'I' => 'mm-dd-yyyy',
            'J' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 16, 'C' => 30, 'D' => 16, 'E' => 16, 'F' => 28,
            'G' => 22, 'H' => 18, 'I' => 17, 'J' => 12, 'K' => 30, 'L' => 60,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Header gaya Mazda: grafit + garis bawah Soul Red
                $header = $sheet->getStyle('A1:L1');
                $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1B2027');
                $header->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $header->getBorders()->getBottom()->setBorderStyle('medium')->getColor()->setRGB('A6192E');
                $sheet->getRowDimension(1)->setRowHeight(22);

                $sheet->getStyle("I1:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:L{$lastRow}");
            },
        ];
    }
}
