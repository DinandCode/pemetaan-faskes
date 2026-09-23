<?php

namespace App\Exports;

use App\Models\Faskes;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FaskesPerKecamatanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected ?string $kecamatan;
    protected ?string $jenisFaskes;
    protected int $rowNumber = 0;

    public function __construct(?string $kecamatan = null, ?string $jenisFaskes = null)
    {
        $this->kecamatan = $kecamatan;
        $this->jenisFaskes = $jenisFaskes;
    }

    /**
     * Ambil data faskes dengan relasi child diurutkan per kecamatan.
     */
    public function collection(): Collection
    {
        $query = Faskes::query()->with([
            'puskesmasDetail',
            'rumahSakitDetail',
            'klinikPratamaDetail',
            'klinikUtamaDetail',
            'laboratoriumDetail',
            'upkdkDetail',
        ]);

        if (! empty($this->kecamatan)) {
            $query->where('kecamatan', $this->kecamatan);
        }

        if (! empty($this->jenisFaskes)) {
            $query->where('jenis_faskes', $this->jenisFaskes);
        }

        return $query->orderBy('kecamatan', 'asc')
            ->orderBy('jenis_faskes', 'asc')
            ->orderBy('nama', 'asc')
            ->get();
    }

    /**
     * Heading kolom Excel.
     */
    public function headings(): array
    {
        return [
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
        ];
    }

    /**
     * Mapping baris data Excel.
     */
    public function map($faskes): array
    {
        $this->rowNumber++;
        $spec = $faskes->detail;

        $layanan = '-';
        $bed = '-';
        $ambulans = 'Tidak';
        $masaIzin = '-';

        if ($faskes->jenis_faskes === 'puskesmas' && $faskes->puskesmasDetail) {
            $isPoned = ($faskes->puskesmasDetail->poned === 'Ya PONED');
            $layanan = ucfirst(str_replace('_', ' ', $faskes->puskesmasDetail->kategori)) . ($isPoned ? ' (PONED)' : '');
            $bed = (string) $faskes->puskesmasDetail->jumlah_tempat_tidur;
            $totalAmb = $faskes->puskesmasDetail->ambulans_transport + $faskes->puskesmasDetail->ambulans_roda_dua;
            $ambulans = $totalAmb > 0 ? "{$totalAmb} Unit" : 'Tidak';
            $masaIzin = $faskes->puskesmasDetail->masa_izin ? $faskes->puskesmasDetail->masa_izin->format('d/m/Y') : '-';
        } elseif ($faskes->jenis_faskes === 'rumah_sakit' && $faskes->rumahSakitDetail) {
            $isPonek = ($faskes->rumahSakitDetail->ponek === 'Ya PONEK');
            $layanan = ($faskes->rumahSakitDetail->kemampuan_pelayanan ?: 'Rumah Sakit Umum') . ($isPonek ? ' (PONEK)' : '');
            $totalAmb = $faskes->rumahSakitDetail->ambulans_gadar + $faskes->rumahSakitDetail->ambulans_transport;
            $ambulans = $totalAmb > 0 ? "{$totalAmb} Unit" : 'Tidak';
            $masaIzin = $faskes->rumahSakitDetail->masa_izin ? $faskes->rumahSakitDetail->masa_izin->format('d/m/Y') : '-';
        } elseif ($faskes->jenis_faskes === 'klinik_pratama' && $faskes->klinikPratamaDetail) {
            $layanan = $faskes->klinikPratamaDetail->jenis_layanan ?: 'Klinik Pratama';
            $bed = (string) $faskes->klinikPratamaDetail->bed_rawat_inap;
            $ambulans = $faskes->klinikPratamaDetail->ambulans_transport > 0 ? "{$faskes->klinikPratamaDetail->ambulans_transport} Unit" : 'Tidak';
            $masaIzin = $faskes->klinikPratamaDetail->masa_izin ? $faskes->klinikPratamaDetail->masa_izin->format('d/m/Y') : '-';
        } elseif ($faskes->jenis_faskes === 'klinik_utama' && $faskes->klinikUtamaDetail) {
            $layanan = $faskes->klinikUtamaDetail->kemampuan_layanan ?: 'Spesialistik';
            $ambulans = $faskes->klinikUtamaDetail->ambulans > 0 ? "{$faskes->klinikUtamaDetail->ambulans} Unit" : 'Tidak';
            $masaIzin = $faskes->klinikUtamaDetail->masa_izin ? $faskes->klinikUtamaDetail->masa_izin->format('d/m/Y') : '-';
        } elseif ($faskes->jenis_faskes === 'laboratorium' && $faskes->laboratoriumDetail) {
            $layanan = $faskes->laboratoriumDetail->jenis_layanan ?: 'Laboratorium Medis';
            $masaIzin = $faskes->laboratoriumDetail->masa_izin ? $faskes->laboratoriumDetail->masa_izin->format('d/m/Y') : '-';
        } elseif ($faskes->jenis_faskes === 'upkdk' && $faskes->upkdkDetail) {
            $tipe = [];
            if ($faskes->upkdkDetail->is_pustu === 'Ya') $tipe[] = 'Pustu';
            if ($faskes->upkdkDetail->is_pkd === 'Ya') $tipe[] = 'PKD';
            $namaTipe = !empty($tipe) ? implode('/', $tipe) : strtoupper($faskes->upkdkDetail->jenis ?? 'UPKDK');
            $layanan = "{$namaTipe} (SDM: {$faskes->upkdkDetail->jumlah_sdm})";
        }

        return [
            $this->rowNumber,
            $faskes->kecamatan ?: '-',
            $faskes->desa ?: '-',
            $faskes->nama,
            ucwords(str_replace('_', ' ', $faskes->jenis_faskes)),
            $faskes->alamat ?: '-',
            $faskes->latitude,
            $faskes->longitude,
            $faskes->nomor_telepon ?: '-',
            ucfirst($faskes->status),
            $layanan,
            $bed,
            $ambulans,
            $masaIzin,
        ];
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
     * Judul sheet Excel.
     */
    public function title(): string
    {
        return 'Rekapitulasi Faskes';
    }
}
