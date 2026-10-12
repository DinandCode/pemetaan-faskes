<?php

namespace App\Exports;

use App\Models\Faskes;
use App\Models\FaskesFieldDefinition;
use App\Support\ExcelSecurityHelper;
use Illuminate\Database\Eloquent\Builder;
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

class FaskesPerKecamatanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected Builder $builder;
    protected int $rowNumber = 0;
    protected Collection $customFieldDefinitions;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->customFieldDefinitions = FaskesFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    /**
     * Ambil data faskes yang sudah difilter secara konsisten.
     */
    public function collection(): Collection
    {
        return $this->builder
            ->orderBy('kecamatan', 'asc')
            ->orderBy('jenis_faskes', 'asc')
            ->orderBy('nama', 'asc')
            ->get();
    }

    /**
     * Heading kolom Excel: mencakup kolom dasar, detail spesifikasi, dan seluruh custom fields dinamis.
     */
    public function headings(): array
    {
        $headers = [
            'No',
            'Kecamatan',
            'Desa / Kelurahan',
            'Nama Fasilitas Kesehatan',
            'Jenis Faskes',
            'Alamat',
            'Latitude',
            'Longitude',
            'Nomor Telepon',
            'Status',
            'Kategori / Layanan Utama',
            'Tempat Tidur',
            'Ambulans',
            'Masa Izin',
            'Penanggung Jawab',
            'Kontak PJ',
            'Kepemilikan',
            'Kerja Sama BPJS',
        ];

        foreach ($this->customFieldDefinitions as $def) {
            $headers[] = $def->label . ($def->is_required ? '*' : '');
        }

        return $headers;
    }

    /**
     * Mapping baris data Excel dengan sanitasi formula injection.
     */
    public function map($faskes): array
    {
        $this->rowNumber++;

        $layanan = '-';
        $bed = '-';
        $ambulans = 'Tidak';
        $masaIzin = '-';
        $pj = '-';
        $kontakPj = '-';
        $kepemilikan = '-';
        $bpjs = '-';

        if ($faskes->jenis_faskes === 'puskesmas' && $faskes->puskesmasDetail) {
            $isPoned = ($faskes->puskesmasDetail->poned === 'Ya PONED');
            $layanan = ucfirst(str_replace('_', ' ', $faskes->puskesmasDetail->kategori ?? '')) . ($isPoned ? ' (PONED)' : '');
            $bed = (string) ($faskes->puskesmasDetail->jumlah_tempat_tidur ?? 0);
            $totalAmb = ($faskes->puskesmasDetail->ambulans_transport ?? 0) + ($faskes->puskesmasDetail->ambulans_roda_dua ?? 0);
            $ambulans = $totalAmb > 0 ? "{$totalAmb} Unit" : 'Tidak';
            $masaIzin = $this->formatDateSafe($faskes->puskesmasDetail->masa_izin ?? null);
            $kepemilikan = 'Pemerintah';
            $bpjs = 'Ya';
        } elseif ($faskes->jenis_faskes === 'rumah_sakit' && $faskes->rumahSakitDetail) {
            $isPonek = ($faskes->rumahSakitDetail->ponek === 'Ya PONEK');
            $tipePrefix = $faskes->rumahSakitDetail->tipe_rs ? 'Tipe ' . $faskes->rumahSakitDetail->tipe_rs . ' - ' : '';
            $layanan = $tipePrefix . ($faskes->rumahSakitDetail->kemampuan_pelayanan ?: 'Rumah Sakit Umum') . ($isPonek ? ' (PONEK)' : '');
            $totalAmb = ($faskes->rumahSakitDetail->ambulans_gadar ?? 0) + ($faskes->rumahSakitDetail->ambulans_transport ?? 0);
            $ambulans = $totalAmb > 0 ? "{$totalAmb} Unit" : 'Tidak';
            $masaIzin = $this->formatDateSafe($faskes->rumahSakitDetail->masa_izin ?? null);
            $bpjs = 'Ya';
        } elseif ($faskes->jenis_faskes === 'klinik_pratama' && $faskes->klinikPratamaDetail) {
            $layanan = $faskes->klinikPratamaDetail->jenis_layanan ?: 'Klinik Pratama';
            $bed = (string) ($faskes->klinikPratamaDetail->bed_rawat_inap ?? 0);
            $ambulans = ($faskes->klinikPratamaDetail->ambulans_transport ?? 0) > 0 ? "{$faskes->klinikPratamaDetail->ambulans_transport} Unit" : 'Tidak';
            $masaIzin = $this->formatDateSafe($faskes->klinikPratamaDetail->masa_izin ?? null);
            $pj = $faskes->klinikPratamaDetail->pj ?: '-';
            $kontakPj = $faskes->klinikPratamaDetail->kontak_pj ?: '-';
            $kepemilikan = $faskes->klinikPratamaDetail->kepemilikan ?: 'Swasta';
            $bpjs = $faskes->klinikPratamaDetail->bpjs ? 'Ya' : 'Tidak';
        } elseif ($faskes->jenis_faskes === 'klinik_utama' && $faskes->klinikUtamaDetail) {
            $layanan = $faskes->klinikUtamaDetail->kemampuan_layanan ?: 'Spesialistik';
            $bed = (string) ($faskes->klinikUtamaDetail->bed_rawat_inap ?? 0);
            $ambulans = ($faskes->klinikUtamaDetail->ambulans ?? 0) > 0 ? "{$faskes->klinikUtamaDetail->ambulans} Unit" : 'Tidak';
            $masaIzin = $this->formatDateSafe($faskes->klinikUtamaDetail->masa_izin ?? null);
            $pj = $faskes->klinikUtamaDetail->pj ?: '-';
            $kontakPj = $faskes->klinikUtamaDetail->kontak_pj ?: '-';
            $kepemilikan = $faskes->klinikUtamaDetail->kepemilikan ?: 'Swasta';
            $bpjs = $faskes->klinikUtamaDetail->bpjs ? 'Ya' : 'Tidak';
        } elseif ($faskes->jenis_faskes === 'laboratorium' && $faskes->laboratoriumDetail) {
            $layanan = $faskes->laboratoriumDetail->jenis_layanan ?: 'Laboratorium Medis';
            $masaIzin = $this->formatDateSafe($faskes->laboratoriumDetail->masa_izin ?? null);
            $kepemilikan = $faskes->laboratoriumDetail->kepemilikan ?: 'Swasta';
        } elseif ($faskes->jenis_faskes === 'upkdk' && $faskes->upkdkDetail) {
            $tipe = [];
            if ($faskes->upkdkDetail->is_pustu === 'Ya') $tipe[] = 'Pustu';
            if ($faskes->upkdkDetail->is_pkd === 'Ya') $tipe[] = 'PKD';
            $namaTipe = !empty($tipe) ? implode('/', $tipe) : strtoupper($faskes->upkdkDetail->jenis ?? 'UPKDK');
            $layanan = "{$namaTipe} (SDM: {$faskes->upkdkDetail->jumlah_sdm})";
            $kepemilikan = 'Pemerintah';
        } elseif ($faskes->jenis_faskes === 'griya_sehat' && $faskes->griyaSehatDetail) {
            $layanan = 'Griya Sehat' . ($faskes->griyaSehatDetail->jumlah_sdm ? " (SDM: {$faskes->griyaSehatDetail->jumlah_sdm})" : '');
            $masaIzin = $this->formatDateSafe($faskes->griyaSehatDetail->masa_izin ?? null);
            $pj = $faskes->griyaSehatDetail->pj ?: '-';
            $kontakPj = $faskes->griyaSehatDetail->kontak_pj ?: '-';
        }

        $row = [
            $this->rowNumber,
            $faskes->kecamatan ?: '-',
            $faskes->desa ?: '-',
            $faskes->nama,
            ucwords(str_replace('_', ' ', $faskes->jenis_faskes)),
            $faskes->alamat ?: '-',
            $faskes->latitude !== null ? (float) $faskes->latitude : '-',
            $faskes->longitude !== null ? (float) $faskes->longitude : '-',
            $faskes->nomor_telepon ?: '-',
            ucfirst($faskes->status),
            $layanan,
            $bed,
            $ambulans,
            $masaIzin,
            $pj,
            $kontakPj,
            $kepemilikan,
            $bpjs,
        ];

        // Format kolom tambahan (10 tipe)
        $fieldValuesCol = $faskes->fieldValues ?? collect();
        $fieldValuesByDef = $fieldValuesCol->keyBy('field_definition_id');
        foreach ($this->customFieldDefinitions as $def) {
            $fv = $fieldValuesByDef->get($def->id);
            if (! $fv) {
                $row[] = '-';
                continue;
            }

            if ($def->type === FaskesFieldDefinition::TYPE_NUMBER) {
                $val = $fv->value_number !== null ? (float) $fv->value_number : ($fv->value !== null ? (float) $fv->value : '-');
            } elseif ($def->type === FaskesFieldDefinition::TYPE_BOOLEAN) {
                $val = $fv->value_bool !== null ? ($fv->value_bool ? 'Ya' : 'Tidak') : ($fv->value ?: '-');
            } elseif ($def->type === FaskesFieldDefinition::TYPE_MULTISELECT) {
                $arr = $fv->value_json ?: ($fv->value ? explode(';', $fv->value) : []);
                $val = ! empty($arr) ? implode('; ', array_map('trim', $arr)) : '-';
            } elseif ($def->type === FaskesFieldDefinition::TYPE_DATE) {
                $val = $fv->value_date ? $fv->value_date->format('d/m/Y') : ($fv->value ?: '-');
            } else {
                $val = $fv->value ?: '-';
            }

            $row[] = $val;
        }

        return ExcelSecurityHelper::sanitizeRow($row);
    }

    /**
     * Styling baris header.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E40AF'], // Blue 800
                ],
            ],
        ];
    }

    /**
     * Freeze pane & auto-filter.
     */
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

    /**
     * Format tanggal secara aman baik dari Carbon maupun string.
     */
    protected function formatDateSafe(mixed $date): string
    {
        if (! $date) {
            return '-';
        }
        if ($date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y');
        }
        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }

    /**
     * Judul sheet Excel.
     */
    public function title(): string
    {
        return 'Rekapitulasi Faskes';
    }
}
