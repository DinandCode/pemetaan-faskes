<?php

namespace App\Exports;

use App\Support\ExcelSecurityHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalisisEventExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
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
            'Peringkat',
            'Prioritas / Tier',
            'Nama Faskes',
            'Jenis Faskes',
            'Kecamatan',
            'Desa / Kelurahan',
            'Alamat',
            'Nomor Telepon',
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

        $rank = $detail['rekomendasi_rank'] ?? $this->rowNumber;
        $tierLabel = $detail['prioritas_label'] ?? (isset($detail['prioritas_tier']) ? "Tier {$detail['prioritas_tier']}" : '-');

        $row = [
            $this->rowNumber,
            $rank,
            $tierLabel,
            $faskes['nama'] ?? '-',
            ucwords(str_replace('_', ' ', $faskes['jenis_faskes'] ?? '-')),
            $faskes['kecamatan'] ?? '-',
            $faskes['desa'] ?? '-',
            $faskes['alamat'] ?? '-',
            $faskes['nomor_telepon'] ?? '-',
            $faskes['latitude'] ?? '-',
            $faskes['longitude'] ?? '-',
            $detail['jarak_lurus']['km'] ?? '-',
            $detail['jarak_jalan']['km'] ?? '-',
            round($detail['estimasi_waktu']['menit'] ?? 0, 1),
            ($detail['rute_tersedia'] ?? false) ? 'Ya' : 'Tidak',
        ];

        return ExcelSecurityHelper::sanitizeRow($row);
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();
                $sheet->setAutoFilter("A1:{$highestColumn}{$highestRow}");
            },
        ];
    }

    public function title(): string
    {
        return 'Analisis Event';
    }
}
