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
 * Export Dealer Personel After Sales (dealer sendiri) ke .xlsx, filter sama dengan tabel di halaman.
 * NIK & No KTP disimpan sebagai teks (16 digit KTP tidak berubah jadi notasi ilmiah); tanggal = tanggal Excel.
 */
class DealerPersonelExport extends DefaultValueBinder implements
    FromGenerator, WithHeadings, WithMapping, WithCustomValueBinder, WithColumnFormatting, WithColumnWidths, WithEvents, WithTitle
{
    /** Kolom berisi tanggal Excel (angka). */
    private const DATE_COLUMNS = ['G', 'I'];

    public function __construct(private Builder $query)
    {
    }

    public function generator(): Generator
    {
        foreach ($this->query->cursor() as $row) {
            yield $row;
        }
    }

    public function title(): string
    {
        return 'Dealer Personel';
    }

    public function headings(): array
    {
        return [
            'NIK', 'Nama', 'Nama Panggilan', 'No KTP', 'Level', 'Jenis', 'Tanggal Masuk',
            'Status', 'Tanggal Keluar', 'Alasan Keluar', 'Black List', 'Black List EMI', 'Alamat',
        ];
    }

    public function map($row): array
    {
        // Hanya bagian tanggal (tanpa konversi timezone) agar tanggal di Excel tidak mundur 1 hari
        $date = fn ($v) => $v ? ExcelDate::stringToExcel(substr($v, 0, 10)) : null;

        return [
            $row->nik,
            $row->name,
            $row->nm_panggilan,
            $row->no_ktp,
            $row->dealer_level_personel,
            $row->jenis,
            $date($row->tgl_masuk),
            $row->is_active ? 'Aktif' : 'Nonaktif',
            $date($row->tgl_keluar),
            $row->alasan_keluar,
            $row->is_blacklist ? 'Ya' : 'Tidak',
            $row->is_blacklist_atpm ? 'Ya' : 'Tidak',
            $row->alamat,
        ];
    }

    /** Kolom tanggal tetap angka; kolom lain selalu teks. */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getRow() > 1 && in_array($cell->getColumn(), self::DATE_COLUMNS, true) && is_numeric($value)) {
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
        return array_fill_keys(self::DATE_COLUMNS, 'dd-mm-yyyy');
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, 'B' => 30, 'C' => 16, 'D' => 20, 'E' => 24, 'F' => 10, 'G' => 14,
            'H' => 11, 'I' => 14, 'J' => 30, 'K' => 11, 'L' => 14, 'M' => 60,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Header gaya Mazda: grafit + garis bawah Soul Red
                $header = $sheet->getStyle('A1:M1');
                $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1B2027');
                $header->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $header->getBorders()->getBottom()->setBorderStyle('medium')->getColor()->setRGB('A6192E');
                $sheet->getRowDimension(1)->setRowHeight(22);

                foreach (['G', 'H', 'I', 'K', 'L'] as $col) {
                    $sheet->getStyle("{$col}1:{$col}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->freezePane('C2');
                $sheet->setAutoFilter("A1:M{$lastRow}");
            },
        ];
    }
}
