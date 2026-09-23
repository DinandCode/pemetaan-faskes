<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalisisEventExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $data;
    protected array $parameter;
    protected int $rowNumber = 0;

    public function __construct(array $data, array $parameter = [])
    {
        $this->data = $data;
        $this->parameter = $parameter;
    }

    public function collection(): Collection
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Faskes',
            'Jenis Faskes',
            'Kecamatan',
            'Alamat',
            'Latitude',
            'Longitude',
            'Jarak Lurus (km)',
            'Jarak Jalan (km)',
            'Estimasi Waktu (menit)',
            'Rute Tersedia',
        ];
    }

    public function map($item): array
    {
        $this->rowNumber++;
        $detail = is_array($item) ? $item : (array) $item;
        $faskes = $detail['detail_faskes'] ?? [];

        return [
            $this->rowNumber,
            $faskes['nama'] ?? '-',
            ucwords(str_replace('_', ' ', $faskes['jenis_faskes'] ?? '-')),
            $faskes['kecamatan'] ?? '-',
            $faskes['alamat'] ?? '-',
            $faskes['latitude'] ?? '-',
            $faskes['longitude'] ?? '-',
            $detail['jarak_lurus']['km'] ?? '-',
            $detail['jarak_jalan']['km'] ?? '-',
            round($detail['estimasi_waktu']['menit'] ?? 0, 1),
            ($detail['rute_tersedia'] ?? false) ? 'Ya' : 'Tidak',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFDC2626'], // Red 600
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Analisis Event';
    }
}
