<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmailSchedulerCkdExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data->map(function ($row) {
            return [
                'kd_dealer' => $row->kd_dealer,
                'nm_dealer' => $row->nm_dealer,
                'no_vin' => $row->no_vin,
                'kd_model' => $row->kd_model,
                'nm_model' => $row->nm_model,
                'kd_type' => $row->kd_type,
                'nm_type' => $row->nm_type,
                'tgl_delivery' => $row->tgl_delivery,
                'log_action_date' => $row->log_action_date,
                'log_action_mode' => $row->log_action_mode,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Kode Dealer',
            'Nama Dealer',
            'No VIN',
            'Kode Model',
            'Nama Model',
            'Kode Type',
            'Nama Type',
            'Tgl Delivery',
            'Log Action Date',
            'Log Action Mode',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002856']
                ],
            ],
        ];
    }
}
