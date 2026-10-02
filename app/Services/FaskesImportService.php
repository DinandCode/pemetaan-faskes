<?php

namespace App\Services;

use App\Models\Faskes;
use App\Models\FaskesFieldDefinition;
use App\Models\FaskesFieldValue;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FaskesImportService
{
    // Bounding Box Kabupaten Banyumas
    public const LAT_MIN = -7.75;
    public const LAT_MAX = -7.15;
    public const LON_MIN = 108.95;
    public const LON_MAX = 109.50;

    public const MAX_ROWS = 1000;
    public const MAX_FILE_SIZE_KB = 4096; // 4 MB

    /**
     * Daftar nama jenis faskes yang didukung beserta labelnya.
     */
    public static function supportedJenis(): array
    {
        return [
            'puskesmas'      => 'Puskesmas',
            'rumah_sakit'    => 'Rumah Sakit',
            'klinik_pratama' => 'Klinik Pratama',
            'klinik_utama'   => 'Klinik Utama',
            'laboratorium'   => 'Laboratorium',
            'upkdk'          => 'UPKDK (Pustu / PKD)',
            'griya_sehat'    => 'Griya Sehat',
            'tpmd'           => 'TPMD (Praktik Mandiri Dokter)',
            'tpmdg'          => 'TPMDG (Praktik Mandiri Dokter Gigi)',
            'tpmb'           => 'TPMB (Praktik Mandiri Bidan)',
            'tpmp'           => 'TPMP (Praktik Mandiri Perawat)',
        ];
    }

    /**
     * Konfigurasi kolom per jenis faskes.
     */
    public static function getColumnSchema(string $jenisFaskes): array
    {
        // 1. Kolom Dasar (Semua Jenis)
        $columns = [
            'nama' => [
                'header'   => 'Nama Faskes*',
                'required' => true,
                'type'     => 'string',
                'desc'     => 'Nama lengkap fasilitas kesehatan (Wajib diisi, maks 255 karakter). Contoh: RSUD Banyumas / Puskesmas Purwokerto Timur.',
            ],
            'status' => [
                'header'   => 'Status*',
                'required' => true,
                'type'     => 'list',
                'options'  => ['Aktif', 'Nonaktif'],
                'desc'     => 'Status operasional faskes (Wajib). Pilih: Aktif atau Nonaktif.',
            ],
            'alamat' => [
                'header'   => 'Alamat',
                'required' => false,
                'type'     => 'string',
                'desc'     => 'Alamat jalan, nomor gedung, atau RT/RW.',
            ],
            'kecamatan' => [
                'header'   => 'Kecamatan',
                'required' => false,
                'type'     => 'list',
                'options'  => [
                    'Ajibarang', 'Banyumas', 'Baturraden', 'Cilongok', 'Gumelar',
                    'Jatilawang', 'Kalibagor', 'Karanglewas', 'Kebasen', 'Kedungbanteng',
                    'Kembaran', 'Kemranjen', 'Lumbir', 'Patikraja', 'Pekuncen',
                    'Purwojati', 'Purwokerto Barat', 'Purwokerto Selatan', 'Purwokerto Timur', 'Purwokerto Utara',
                    'Rawalo', 'Sokaraja', 'Somagede', 'Sumbang', 'Sumpiuh',
                    'Tambak', 'Wangon',
                ],
                'desc'     => 'Nama kecamatan di Kabupaten Banyumas.',
            ],
            'desa' => [
                'header'   => 'Desa / Kelurahan',
                'required' => false,
                'type'     => 'string',
                'desc'     => 'Nama desa atau kelurahan lokasi faskes.',
            ],
            'nomor_telepon' => [
                'header'   => 'Nomor Telepon',
                'required' => false,
                'type'     => 'string',
                'desc'     => 'Nomor telepon kantor / kontak resmi (maks 50 karakter).',
            ],
            'latitude' => [
                'header'   => 'Latitude*',
                'required' => true,
                'type'     => 'decimal',
                'desc'     => 'Garis lintang koordinat WGS84 di Kabupaten Banyumas (antara -7.75 s.d -7.15). Format angka desimal menggunakan titik atau koma. Contoh: -7.424364',
            ],
            'longitude' => [
                'header'   => 'Longitude*',
                'required' => true,
                'type'     => 'decimal',
                'desc'     => 'Garis bujur koordinat WGS84 di Kabupaten Banyumas (antara 108.95 s.d 109.50). Format angka desimal menggunakan titik atau koma. Contoh: 109.230345',
            ],
        ];

        // 2. Kolom Spesifik Per Jenis
        switch ($jenisFaskes) {
            case 'puskesmas':
                $columns['wilayah'] = [
                    'header'   => 'Wilayah',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Perkotaan', 'Perdesaan'],
                    'desc'     => 'Klasifikasi wilayah kerja Puskesmas (Perkotaan / Perdesaan).',
                ];
                $columns['kategori'] = [
                    'header'   => 'Kategori Layanan',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Rawat Inap', 'Non Rawat Inap'],
                    'desc'     => 'Kategori kemampuan rawat (Rawat Inap / Non Rawat Inap).',
                ];
                $columns['persalinan'] = [
                    'header'   => 'Kemampuan Persalinan',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['PONED', 'NON PONED (Mampu Salin)', 'NON PONED (Tidak Mampu Salin)'],
                    'desc'     => 'Status fasilitas dan kesiapan layanan persalinan.',
                ];
                $columns['jumlah_tempat_tidur'] = [
                    'header'   => 'Jumlah TT Rawat Inap',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah kapasitas tempat tidur rawat inap (angka >= 0).',
                ];
                $columns['ambulans_transport'] = [
                    'header'   => 'Ambulans Transport',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah unit ambulans transport (angka >= 0).',
                ];
                $columns['ambulans_roda_dua'] = [
                    'header'   => 'Ambulans Roda Dua',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah unit ambulans sepeda motor / roda dua (angka >= 0).',
                ];
                $columns['masa_izin'] = [
                    'header'   => 'Masa Berlaku Izin',
                    'required' => false,
                    'type'     => 'date',
                    'desc'     => 'Tanggal berakhirnya izin operasional. Format: YYYY-MM-DD (Contoh: 2028-12-31).',
                ];
                $columns['jumlah_sdm'] = [
                    'header'   => 'Jumlah SDM',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Total tenaga kesehatan dan non-kesehatan (angka >= 0).',
                ];
                break;

            case 'rumah_sakit':
                $columns['tipe_rs'] = [
                    'header'   => 'Tipe RS',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['A', 'B', 'C', 'D', 'D Pratama'],
                    'desc'     => 'Kelas/tipe Rumah Sakit (A / B / C / D / D Pratama).',
                ];
                $columns['kemampuan_pelayanan'] = [
                    'header'   => 'Kemampuan Pelayanan',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Deskripsi fasilitas & kemampuan pelayanan medis spesialistik.',
                ];
                $columns['ponek'] = [
                    'header'   => 'PONEK',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['PONEK', 'NON PONEK'],
                    'desc'     => 'Pelayanan Obstetri Neonatal Emergensi Komprehensif.',
                ];
                $columns['ambulans_transport'] = [
                    'header'   => 'Ambulans Transport',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah armada ambulans transport pasien (angka >= 0).',
                ];
                $columns['ambulans_gadar'] = [
                    'header'   => 'Ambulans Gadar',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah ambulans gawat darurat / ICU mobile (angka >= 0).',
                ];
                $columns['masa_izin'] = [
                    'header'   => 'Masa Berlaku Izin',
                    'required' => false,
                    'type'     => 'date',
                    'desc'     => 'Masa berlaku izin operasional (YYYY-MM-DD).',
                ];
                break;

            case 'klinik_pratama':
                $columns['kategori_layanan'] = [
                    'header'   => 'Kategori Layanan',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Rawat Jalan', 'Rawat Inap'],
                    'desc'     => 'Kemampuan layanan klinik (Rawat Jalan / Rawat Inap).',
                ];
                $columns['jenis_layanan'] = [
                    'header'   => 'Jenis Layanan',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Rincian jenis layanan medis yang disediakan.',
                ];
                $columns['kepemilikan'] = [
                    'header'   => 'Kepemilikan',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Swasta', 'Pemerintah'],
                    'desc'     => 'Badan pemilik faskes (Swasta / Pemerintah).',
                ];
                $columns['bpjs'] = [
                    'header'   => 'Kerja Sama BPJS',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Ya', 'Tidak'],
                    'desc'     => 'Apakah klinik melayani peserta BPJS Kesehatan (Ya / Tidak).',
                ];
                $columns['bed_rawat_inap'] = [
                    'header'   => 'Jumlah TT Rawat Inap',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah tempat tidur rawat inap klinik (angka >= 0).',
                ];
                $columns['jumlah_sdm'] = [
                    'header'   => 'Jumlah SDM',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah total tenaga kerja medis & paramedis (angka >= 0).',
                ];
                $columns['ambulans_transport'] = [
                    'header'   => 'Ambulans Transport',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah armada ambulans klinik (angka >= 0).',
                ];
                $columns['masa_izin'] = [
                    'header'   => 'Masa Berlaku Izin',
                    'required' => false,
                    'type'     => 'date',
                    'desc'     => 'Masa berlaku izin operasional (YYYY-MM-DD).',
                ];
                $columns['pj'] = [
                    'header'   => 'Nama Penanggung Jawab',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Nama dokter penanggung jawab klinik (PJ).',
                ];
                $columns['kontak_pj'] = [
                    'header'   => 'Nomor Kontak PJ',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Nomor telepon / kontak penanggung jawab.',
                ];
                break;

            case 'klinik_utama':
                $columns['kategori_layanan'] = [
                    'header'   => 'Kategori Layanan',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Rawat Jalan', 'Rawat Inap'],
                    'desc'     => 'Kemampuan layanan klinik (Rawat Jalan / Rawat Inap).',
                ];
                $columns['kemampuan_layanan'] = [
                    'header'   => 'Kemampuan Layanan',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Rincian pelayanan medis spesialistik yang tersedia.',
                ];
                $columns['kepemilikan'] = [
                    'header'   => 'Kepemilikan',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Swasta', 'Pemerintah'],
                    'desc'     => 'Badan kepemilikan klinik (Swasta / Pemerintah).',
                ];
                $columns['bpjs'] = [
                    'header'   => 'Kerja Sama BPJS',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Ya', 'Tidak'],
                    'desc'     => 'Status kerja sama BPJS (Ya / Tidak).',
                ];
                $columns['bed_rawat_inap'] = [
                    'header'   => 'Jumlah TT Rawat Inap',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah tempat tidur rawat inap (angka >= 0).',
                ];
                $columns['ambulans'] = [
                    'header'   => 'Ambulans',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah armada ambulans (angka >= 0).',
                ];
                $columns['masa_izin'] = [
                    'header'   => 'Masa Berlaku Izin',
                    'required' => false,
                    'type'     => 'date',
                    'desc'     => 'Masa berlaku izin operasional (YYYY-MM-DD).',
                ];
                $columns['pj'] = [
                    'header'   => 'Nama Penanggung Jawab',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Nama dokter penanggung jawab.',
                ];
                $columns['kontak_pj'] = [
                    'header'   => 'Nomor Kontak PJ',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Nomor kontak dokter penanggung jawab.',
                ];
                break;

            case 'laboratorium':
                $columns['jenis_layanan'] = [
                    'header'   => 'Jenis Layanan',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Jenis pemeriksaan laboratorium (Klinik umum, patologi, dll).',
                ];
                $columns['kepemilikan'] = [
                    'header'   => 'Kepemilikan',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Swasta', 'Pemerintah'],
                    'desc'     => 'Badan kepemilikan laboratorium (Swasta / Pemerintah).',
                ];
                $columns['masa_izin'] = [
                    'header'   => 'Masa Berlaku Izin',
                    'required' => false,
                    'type'     => 'date',
                    'desc'     => 'Masa berlaku izin operasional (YYYY-MM-DD).',
                ];
                break;

            case 'upkdk':
                $columns['is_pustu'] = [
                    'header'   => 'Pustu',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Ya', 'Tidak'],
                    'desc'     => 'Apakah unit berfungsi sebagai Puskesmas Pembantu (Ya / Tidak).',
                ];
                $columns['is_pkd'] = [
                    'header'   => 'PKD',
                    'required' => false,
                    'type'     => 'list',
                    'options'  => ['Ya', 'Tidak'],
                    'desc'     => 'Apakah unit berfungsi sebagai Pos Kesehatan Desa (Ya / Tidak).',
                ];
                $columns['jumlah_sdm'] = [
                    'header'   => 'Jumlah SDM',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah tenaga pelaksana di unit (angka >= 0).',
                ];
                break;

            case 'griya_sehat':
                $columns['masa_izin'] = [
                    'header'   => 'Masa Berlaku Izin',
                    'required' => false,
                    'type'     => 'date',
                    'desc'     => 'Masa berlaku izin operasional Griya Sehat (YYYY-MM-DD).',
                ];
                $columns['pj'] = [
                    'header'   => 'Nama Penanggung Jawab',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Nama tenaga kesehatan penanggung jawab.',
                ];
                $columns['kontak_pj'] = [
                    'header'   => 'Nomor Kontak PJ',
                    'required' => false,
                    'type'     => 'string',
                    'desc'     => 'Nomor kontak penanggung jawab.',
                ];
                $columns['jumlah_sdm'] = [
                    'header'   => 'Jumlah SDM',
                    'required' => false,
                    'type'     => 'integer',
                    'desc'     => 'Jumlah tenaga terapis / SDM (angka >= 0).',
                ];
                break;

            case 'tpmd':
            case 'tpmdg':
            case 'tpmb':
            case 'tpmp':
                // Hanya kolom dasar
                break;
        }

        // 3. Kolom Tambahan Dinamis (Custom Fields Tugas 2)
        $definitions = FaskesFieldDefinition::where('is_active', true)
            ->where(function ($q) use ($jenisFaskes) {
                $q->whereNull('jenis_faskes')->orWhere('jenis_faskes', $jenisFaskes);
            })
            ->orderBy('sort_order')
            ->get();

        foreach ($definitions as $def) {
            $key = "custom_{$def->id}";
            $reqMark = $def->is_required ? '*' : '';
            $columns[$key] = [
                'header'        => "{$def->label}{$reqMark}",
                'required'      => (bool) $def->is_required,
                'type'          => 'list',
                'options'       => (array) ($def->options ?? []),
                'desc'          => "Kolom tambahan: {$def->label}." . ($def->is_required ? ' (Wajib)' : ' (Opsional)') . ' Pilihan: ' . implode(', ', (array) ($def->options ?? [])),
                'is_custom'     => true,
                'definition_id' => $def->id,
                'field_key'     => $def->field_key,
            ];
        }

        return $columns;
    }

    /**
     * Menghasilkan dan men-stream file template Excel (.xlsx) per jenis faskes.
     */
    public static function generateTemplate(string $jenisFaskes): StreamedResponse
    {
        $schema = self::getColumnSchema($jenisFaskes);
        $jenisLabel = self::supportedJenis()[$jenisFaskes] ?? ucfirst($jenisFaskes);

        $spreadsheet = new Spreadsheet();

        // ---------------------------------------------------------
        // SHEET 1: Data
        // ---------------------------------------------------------
        $sheetData = $spreadsheet->getActiveSheet();
        $sheetData->setTitle('Data Faskes');

        // Tulis baris header
        $colIdx = 1;
        foreach ($schema as $key => $col) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheetData->setCellValue("{$colLetter}1", $col['header']);

            // Styling Header
            $sheetData->getStyle("{$colLetter}1")->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size'  => 10,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $col['required'] ? '1E40AF' : '334155'], // Biru untuk wajib, slate untuk opsional
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);

            // Data Validation untuk baris 2 s.d 1001
            if ($col['type'] === 'list' && ! empty($col['options'])) {
                // Formula list harus dibatasi maks 255 karakter jika inline
                $optionsStr = '"' . implode(',', $col['options']) . '"';
                for ($row = 2; $row <= 1001; $row++) {
                    $validation = $sheetData->getCell("{$colLetter}{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Pilihan Tidak Valid');
                    $validation->setError('Silakan pilih salah satu opsi dari daftar dropdown.');
                    $validation->setPromptTitle("Pilihan {$col['header']}");
                    $validation->setPrompt('Pilih dari dropdown.');
                    $validation->setFormula1($optionsStr);
                }
            } elseif ($col['type'] === 'integer') {
                for ($row = 2; $row <= 1001; $row++) {
                    $validation = $sheetData->getCell("{$colLetter}{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_WHOLE);
                    $validation->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
                    $validation->setFormula1('0');
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Harus Angka Bulat');
                    $validation->setError('Nilai harus berupa angka bulat lebih besar atau sama dengan 0.');
                }
            }

            // Auto-size column width
            $sheetData->getColumnDimension($colLetter)->setAutoSize(true);

            $colIdx++;
        }

        $sheetData->getRowDimension(1)->setRowHeight(28);
        $sheetData->freezePane('A2');

        // ---------------------------------------------------------
        // SHEET 2: Petunjuk Pengisian
        // ---------------------------------------------------------
        $sheetHelp = $spreadsheet->createSheet();
        $sheetHelp->setTitle('Petunjuk');

        // Judul Petunjuk
        $sheetHelp->setCellValue('A1', "PETUNJUK PENGISIAN TEMPLATE IMPORT: {$jenisLabel}");
        $sheetHelp->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('1E3A8A');

        $sheetHelp->setCellValue('A2', 'Kabupaten Banyumas &bull; Dinas Kesehatan Kabupaten Banyumas');
        $sheetHelp->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('64748B');

        // Panduan Umum
        $sheetHelp->setCellValue('A4', 'KETENTUAN PENTING:');
        $sheetHelp->getStyle('A4')->getFont()->setBold(true)->setSize(10);

        $rules = [
            '1. Kolom bertanda bintang (*) WAJIB DIISI pada setiap baris data.',
            '2. Format koordinat Latitude & Longitude harus desimal dan berada di wilayah Kabupaten Banyumas (Latitude: -7.75 s.d -7.15, Longitude: 108.95 s.d 109.50). Contoh: Latitude: -7.424364, Longitude: 109.230345. Format koma maupun titik dapat diterima.',
            '3. Format tanggal izin operasional menggunakan YYYY-MM-DD (Tahun-Bulan-Tanggal, contoh: 2028-12-31).',
            '4. Maksimal baris data per file adalah 1.000 baris (di luar baris header). Ukuran file maksimal 4 MB.',
            '5. Jangan mengubah, menambah, atau memindahkan urutan nama kolom pada baris header (Baris 1 di sheet Data).',
        ];

        $rIdx = 5;
        foreach ($rules as $rule) {
            $sheetHelp->setCellValue("A{$rIdx}", $rule);
            $sheetHelp->getStyle("A{$rIdx}")->getFont()->setSize(9)->getColor()->setRGB('334155');
            $rIdx++;
        }

        // Tabel Rincian Kolom
        $tableHeaderRow = $rIdx + 1;
        $sheetHelp->setCellValue("A{$tableHeaderRow}", 'Nama Kolom');
        $sheetHelp->setCellValue("B{$tableHeaderRow}", 'Wajib/Opsional');
        $sheetHelp->setCellValue("C{$tableHeaderRow}", 'Tipe Data / Pilihan');
        $sheetHelp->setCellValue("D{$tableHeaderRow}", 'Keterangan & Contoh Pengisian');

        $sheetHelp->getStyle("A{$tableHeaderRow}:D{$tableHeaderRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheetHelp->getRowDimension($tableHeaderRow)->setRowHeight(22);

        $currRow = $tableHeaderRow + 1;
        foreach ($schema as $key => $col) {
            $sheetHelp->setCellValue("A{$currRow}", $col['header']);
            $sheetHelp->setCellValue("B{$currRow}", $col['required'] ? 'Wajib' : 'Opsional');
            $sheetHelp->setCellValue("C{$currRow}", $col['type'] === 'list' ? implode(', ', $col['options']) : strtoupper($col['type']));
            $sheetHelp->setCellValue("D{$currRow}", $col['desc']);

            $sheetHelp->getStyle("B{$currRow}")->getFont()->setBold($col['required'])->getColor()->setRGB($col['required'] ? 'DC2626' : '64748B');
            $sheetHelp->getStyle("A{$currRow}:D{$currRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'font'    => ['size' => 9],
            ]);
            $currRow++;
        }

        foreach (['A', 'B', 'C', 'D'] as $colLetter) {
            $sheetHelp->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Aktifkan kembali Sheet 1 sebagai sheet default saat file dibuka
        $spreadsheet->setActiveSheetIndex(0);

        // Stream output
        $cleanJenis = preg_replace('/[^a-z0-9_-]/', '_', strtolower($jenisFaskes));
        $filename = "template-import-{$cleanJenis}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Membaca dan memvalidasi file Excel import.
     * Mengembalikan laporan lengkap (valid_count, error_count, duplicate_count, errors per baris).
     */
    public function parseAndValidate(UploadedFile $file, string $jenisFaskes, array $options = []): array
    {
        $schema = self::getColumnSchema($jenisFaskes);

        // 1. Validasi ekstensi & ukuran
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            return [
                'success' => false,
                'message' => 'Format file tidak didukung. Harap unggah file dengan format .xlsx, .xls, atau .csv.',
            ];
        }

        if ($file->getSize() > self::MAX_FILE_SIZE_KB * 1024) {
            return [
                'success' => false,
                'message' => 'Ukuran file melebihi batas maksimal 4 MB.',
            ];
        }

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $highestCol = $sheet->getHighestDataColumn();
        } catch (\Exception $e) {
            Log::error('FaskesImportService IOFactory Load Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal membaca isi file Excel: ' . $e->getMessage(),
            ];
        }

        // Cek baris data (baris 1 = header)
        $dataRowCount = $highestRow - 1;
        if ($dataRowCount <= 0) {
            return [
                'success' => false,
                'message' => 'File Excel kosong atau tidak memiliki baris data di bawah header.',
            ];
        }

        if ($dataRowCount > self::MAX_ROWS) {
            return [
                'success' => false,
                'message' => "File memiliki {$dataRowCount} baris data, melebihi batas maksimal " . self::MAX_ROWS . ' baris.',
            ];
        }

        // 2. Petakan header kolom
        $headerRow = $sheet->rangeToArray("A1:{$highestCol}1", null, true, true, true)[1] ?? [];
        $headerMap = []; // index kolom -> schema key

        foreach ($headerRow as $colLetter => $headerText) {
            $cleanHeader = trim((string) $headerText);
            if ($cleanHeader === '') continue;

            // Cocokkan dengan schema (dengan atau tanpa tanda *)
            $matchedKey = null;
            foreach ($schema as $key => $colDef) {
                $targetHeader = $colDef['header'];
                $targetHeaderClean = rtrim($targetHeader, '*');
                $inputHeaderClean = rtrim($cleanHeader, '*');

                if (strcasecmp($cleanHeader, $targetHeader) === 0 ||
                    strcasecmp($inputHeaderClean, $targetHeaderClean) === 0 ||
                    strcasecmp($cleanHeader, $key) === 0) {
                    $matchedKey = $key;
                    break;
                }
            }

            if ($matchedKey) {
                $headerMap[$colLetter] = $matchedKey;
            }
        }

        // Validasi ketersediaan kolom wajib utama di header
        $requiredHeaderKeys = ['nama', 'status', 'latitude', 'longitude'];
        $foundHeaderKeys = array_values($headerMap);
        foreach ($requiredHeaderKeys as $reqKey) {
            if (! in_array($reqKey, $foundHeaderKeys, true)) {
                $expected = $schema[$reqKey]['header'] ?? $reqKey;
                return [
                    'success' => false,
                    'message' => "Kolom wajib '{$expected}' tidak ditemukan di baris header file Excel.",
                ];
            }
        }

        // 3. Baca dan validasi setiap baris
        $parsedRows = [];
        $errors = [];
        $validCount = 0;
        $duplicateCount = 0;
        $seenDuplicates = [];

        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
            $rowData = $sheet->rangeToArray("A{$rowNum}:{$highestCol}{$rowNum}", null, true, true, true)[$rowNum] ?? [];

            // Lewati jika baris benar-benar kosong
            $allEmpty = true;
            foreach ($rowData as $val) {
                if ($val !== null && trim((string) $val) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) continue;

            $rowRecord = [];
            $rowErrors = [];

            // Ekstrak data berdasarkan headerMap
            foreach ($headerMap as $colLetter => $key) {
                $cellVal = $rowData[$colLetter] ?? null;
                $rowRecord[$key] = $cellVal;
            }

            // Validasi: Nama Faskes
            $nama = trim((string) ($rowRecord['nama'] ?? ''));
            if ($nama === '') {
                $rowErrors[] = [
                    'row'     => $rowNum,
                    'column'  => 'Nama Faskes',
                    'message' => 'Nama faskes wajib diisi.',
                ];
            } elseif (mb_strlen($nama) > 255) {
                $rowErrors[] = [
                    'row'     => $rowNum,
                    'column'  => 'Nama Faskes',
                    'message' => 'Nama faskes terlalu panjang (maksimal 255 karakter).',
                ];
            }

            // Validasi: Status
            $rawStatus = strtolower(trim((string) ($rowRecord['status'] ?? 'aktif')));
            if ($rawStatus === 'aktif' || $rawStatus === 'active' || $rawStatus === '1') {
                $rowRecord['status'] = 'aktif';
            } elseif ($rawStatus === 'nonaktif' || $rawStatus === 'non-aktif' || $rawStatus === 'inactive' || $rawStatus === '0') {
                $rowRecord['status'] = 'nonaktif';
            } else {
                $rowErrors[] = [
                    'row'     => $rowNum,
                    'column'  => 'Status',
                    'message' => "Status harus bernilai 'Aktif' atau 'Nonaktif'.",
                ];
            }

            // Validasi: Latitude
            $latRaw = $rowRecord['latitude'] ?? null;
            $lat = $this->parseCoordinateStrict($latRaw);
            if ($lat === null) {
                $rowErrors[] = [
                    'row'     => $rowNum,
                    'column'  => 'Latitude',
                    'message' => 'Latitude tidak valid atau format rusak. Gunakan angka desimal (contoh: -7.424364).',
                ];
            } elseif ($lat < self::LAT_MIN || $lat > self::LAT_MAX) {
                $rowErrors[] = [
                    'row'     => $rowNum,
                    'column'  => 'Latitude',
                    'message' => "Latitude ({$lat}) berada di luar wilayah Kabupaten Banyumas (antara " . self::LAT_MIN . ' s.d ' . self::LAT_MAX . ').',
                ];
            } else {
                $rowRecord['latitude'] = $lat;
            }

            // Validasi: Longitude
            $lngRaw = $rowRecord['longitude'] ?? null;
            $lng = $this->parseCoordinateStrict($lngRaw);
            if ($lng === null) {
                $rowErrors[] = [
                    'row'     => $rowNum,
                    'column'  => 'Longitude',
                    'message' => 'Longitude tidak valid atau format rusak. Gunakan angka desimal (contoh: 109.230345).',
                ];
            } elseif ($lng < self::LON_MIN || $lng > self::LON_MAX) {
                $rowErrors[] = [
                    'row'     => $rowNum,
                    'column'  => 'Longitude',
                    'message' => "Longitude ({$lng}) berada di luar wilayah Kabupaten Banyumas (antara " . self::LON_MIN . ' s.d ' . self::LON_MAX . ').',
                ];
            } else {
                $rowRecord['longitude'] = $lng;
            }

            // Validasi & Sanitasi Kolom Spesifik & Tambahan
            foreach ($schema as $key => $colDef) {
                if (in_array($key, ['nama', 'status', 'latitude', 'longitude'], true)) {
                    continue;
                }

                $val = $rowRecord[$key] ?? null;

                // Cek kolom wajib
                if ($colDef['required'] && ($val === null || trim((string) $val) === '')) {
                    $rowErrors[] = [
                        'row'     => $rowNum,
                        'column'  => $colDef['header'],
                        'message' => "Kolom '{$colDef['header']}' wajib dipilih/diisi.",
                    ];
                    continue;
                }

                if ($val === null || trim((string) $val) === '') {
                    $rowRecord[$key] = null;
                    continue;
                }

                $valStr = trim((string) $val);

                // Tipe Integer
                if ($colDef['type'] === 'integer') {
                    if (! is_numeric($valStr) || (int) $valStr < 0) {
                        $rowErrors[] = [
                            'row'     => $rowNum,
                            'column'  => $colDef['header'],
                            'message' => "Kolom '{$colDef['header']}' harus berupa angka bulat &ge; 0.",
                        ];
                    } else {
                        $rowRecord[$key] = (int) $valStr;
                    }
                }
                // Tipe Date
                elseif ($colDef['type'] === 'date') {
                    $parsedDate = $this->parseDateStrict($val);
                    if ($parsedDate === null) {
                        $rowErrors[] = [
                            'row'     => $rowNum,
                            'column'  => $colDef['header'],
                            'message' => "Format tanggal pada kolom '{$colDef['header']}' tidak valid. Gunakan format YYYY-MM-DD.",
                        ];
                    } else {
                        $rowRecord[$key] = $parsedDate;
                    }
                }
                // Tipe List Dropdown
                elseif ($colDef['type'] === 'list' && ! empty($colDef['options'])) {
                    $matchedOption = null;
                    foreach ($colDef['options'] as $opt) {
                        if (strcasecmp($valStr, $opt) === 0) {
                            $matchedOption = $opt;
                            break;
                        }
                    }

                    if ($matchedOption !== null) {
                        $rowRecord[$key] = $matchedOption;
                    } else {
                        $rowErrors[] = [
                            'row'     => $rowNum,
                            'column'  => $colDef['header'],
                            'message' => "Nilai '{$valStr}' pada kolom '{$colDef['header']}' tidak valid. Opsi yang tersedia: " . implode(', ', $colDef['options']) . '.',
                        ];
                    }
                } else {
                    $rowRecord[$key] = $valStr;
                }
            }

            // Cek Duplikat di database & di file saat ini
            $kecamatanVal = trim((string) ($rowRecord['kecamatan'] ?? ''));
            $dupKey = mb_strtolower($nama) . '|' . mb_strtolower($kecamatanVal);

            $isDuplicateInDb = Faskes::where('jenis_faskes', $jenisFaskes)
                ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
                ->where(function ($q) use ($kecamatanVal) {
                    if ($kecamatanVal === '') {
                        $q->whereNull('kecamatan')->orWhere('kecamatan', '');
                    } else {
                        $q->whereRaw('LOWER(kecamatan) = ?', [mb_strtolower($kecamatanVal)]);
                    }
                })
                ->exists();

            $isDuplicate = $isDuplicateInDb || isset($seenDuplicates[$dupKey]);
            $seenDuplicates[$dupKey] = true;

            if ($isDuplicate) {
                $duplicateCount++;
                $rowRecord['_is_duplicate'] = true;
            } else {
                $rowRecord['_is_duplicate'] = false;
            }

            if (! empty($rowErrors)) {
                $errors = array_merge($errors, $rowErrors);
                $rowRecord['_has_error'] = true;
                $rowRecord['_errors'] = $rowErrors;
            } else {
                $rowRecord['_has_error'] = false;
                $validCount++;
            }

            $rowRecord['_excel_row'] = $rowNum;
            $parsedRows[] = $rowRecord;
        }

        return [
            'success'         => true,
            'total_rows'      => count($parsedRows),
            'valid_count'     => $validCount,
            'error_count'     => count($errors),
            'duplicate_count' => $duplicateCount,
            'errors'          => $errors,
            'rows_data'       => $parsedRows,
        ];
    }

    /**
     * Mengeksekusi penyimpanan data ke database dalam transaksi.
     */
    public function executeImport(array $parsedRows, string $jenisFaskes, array $options = []): array
    {
        $onDuplicate = $options['on_duplicate'] ?? 'skip'; // 'skip' atau 'update'
        $mode = $options['transaction_mode'] ?? 'valid_only'; // 'valid_only' atau 'rollback_all'

        $schema = self::getColumnSchema($jenisFaskes);

        // Jika mode rollback_all dan terdapat baris yang error, gagalkan sebelum DB transaction
        if ($mode === 'rollback_all') {
            $hasAnyError = false;
            foreach ($parsedRows as $r) {
                if (! empty($r['_has_error'])) {
                    $hasAnyError = true;
                    break;
                }
            }
            if ($hasAnyError) {
                return [
                    'success'  => false,
                    'message'  => 'Proses impor dibatalkan karena mode "Batalkan semua jika ada error" aktif dan ditemukan kesalahan validasi pada file.',
                    'imported' => 0,
                    'updated'  => 0,
                    'skipped'  => 0,
                    'failed'   => count($parsedRows),
                ];
            }
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($parsedRows as $row) {
                // Baris yang memiliki error dilewati
                if (! empty($row['_has_error'])) {
                    $failed++;
                    continue;
                }

                $nama = $row['nama'];
                $kecamatan = $row['kecamatan'];

                // Cek eksistensi untuk perlakuan duplikat
                $existingFaskes = Faskes::where('jenis_faskes', $jenisFaskes)
                    ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
                    ->where(function ($q) use ($kecamatan) {
                        if (empty($kecamatan)) {
                            $q->whereNull('kecamatan')->orWhere('kecamatan', '');
                        } else {
                            $q->whereRaw('LOWER(kecamatan) = ?', [mb_strtolower($kecamatan)]);
                        }
                    })
                    ->first();

                if ($existingFaskes) {
                    if ($onDuplicate === 'skip') {
                        $skipped++;
                        continue;
                    }
                    // Mode update
                    $faskes = $existingFaskes;
                    $faskes->update([
                        'status'        => $row['status'],
                        'alamat'        => $row['alamat'],
                        'desa'          => $row['desa'],
                        'nomor_telepon' => $row['nomor_telepon'],
                        'latitude'      => $row['latitude'],
                        'longitude'     => $row['longitude'],
                    ]);
                    $updated++;
                } else {
                    // Mode insert faskes baru
                    $faskes = Faskes::create([
                        'nama'          => $nama,
                        'jenis_faskes'  => $jenisFaskes,
                        'status'        => $row['status'],
                        'alamat'        => $row['alamat'],
                        'kecamatan'     => $kecamatan,
                        'desa'          => $row['desa'],
                        'nomor_telepon' => $row['nomor_telepon'],
                        'latitude'      => $row['latitude'],
                        'longitude'     => $row['longitude'],
                    ]);
                    $imported++;
                }

                // Simpan data child detail
                $this->saveChildDetail($faskes, $jenisFaskes, $row);

                // Simpan kolom tambahan dinamis (custom fields)
                foreach ($schema as $key => $colDef) {
                    if (! empty($colDef['is_custom']) && isset($row[$key]) && $row[$key] !== null) {
                        FaskesFieldValue::updateOrCreate(
                            [
                                'faskes_id'           => $faskes->id,
                                'field_definition_id' => $colDef['definition_id'],
                            ],
                            [
                                'value' => $row[$key],
                            ]
                        );
                    }
                }
            }

            DB::commit();

            return [
                'success'  => true,
                'imported' => $imported,
                'updated'  => $updated,
                'skipped'  => $skipped,
                'failed'   => $failed,
                'total'    => count($parsedRows),
                'message'  => "Impor data {$jenisFaskes} selesai: {$imported} faskes baru berhasil ditambahkan" .
                              ($updated > 0 ? ", {$updated} faskes diperbarui" : '') .
                              ($skipped > 0 ? ", {$skipped} faskes dilewati" : '') .
                              ($failed > 0 ? ", {$failed} baris gagal" : '') . '.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FaskesImportService executeImport Exception: ' . $e->getMessage(), [
                'jenis_faskes' => $jenisFaskes,
                'trace'        => $e->getTraceAsString(),
            ]);

            return [
                'success'  => false,
                'message'  => 'Terjadi kesalahan sistem saat menyimpan data ke database: ' . $e->getMessage(),
                'imported' => 0,
                'updated'  => 0,
                'skipped'  => 0,
                'failed'   => count($parsedRows),
            ];
        }
    }

    /**
     * Menyimpan data child detail spesifikasi ke tabel yang sesuai.
     */
    protected function saveChildDetail(Faskes $faskes, string $jenisFaskes, array $row): void
    {
        switch ($jenisFaskes) {
            case 'puskesmas':
                $persalinan = $row['persalinan'] ?? null;
                $poned = 'Tidak PONED';
                $mampuSalin = 'Tidak';
                if ($persalinan === 'PONED') {
                    $poned = 'Ya PONED';
                    $mampuSalin = 'Ya';
                } elseif ($persalinan === 'NON PONED (Mampu Salin)') {
                    $poned = 'Tidak PONED';
                    $mampuSalin = 'Ya';
                } elseif ($persalinan === 'NON PONED (Tidak Mampu Salin)') {
                    $poned = 'Tidak PONED';
                    $mampuSalin = 'Tidak';
                }

                $faskes->puskesmasDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'kategori'            => ($row['kategori'] ?? '') === 'Rawat Inap' ? 'rawat_inap' : 'rawat_jalan',
                        'poned'               => $poned,
                        'mampu_salin'         => $mampuSalin,
                        'jumlah_tempat_tidur' => (int) ($row['jumlah_tempat_tidur'] ?? 0),
                        'ambulans_transport'  => (int) ($row['ambulans_transport'] ?? 0),
                        'ambulans_roda_dua'   => (int) ($row['ambulans_roda_dua'] ?? 0),
                        'masa_izin'           => $row['masa_izin'] ?? null,
                        'jumlah_sdm'          => (int) ($row['jumlah_sdm'] ?? 0),
                        'wilayah'             => ($row['wilayah'] ?? '') === 'Perdesaan' ? 'pedesaan' : 'perkotaan',
                    ]
                );
                break;

            case 'rumah_sakit':
                $faskes->rumahSakitDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'tipe_rs'             => $row['tipe_rs'] ?? null,
                        'kemampuan_pelayanan' => $row['kemampuan_pelayanan'] ?? null,
                        'ponek'               => ($row['ponek'] ?? '') === 'PONEK' ? 'PONEK' : 'Tidak PONEK',
                        'ambulans_transport'  => (int) ($row['ambulans_transport'] ?? 0),
                        'ambulans_gadar'      => (int) ($row['ambulans_gadar'] ?? 0),
                        'masa_izin'           => $row['masa_izin'] ?? null,
                    ]
                );
                break;

            case 'klinik_pratama':
                $faskes->klinikPratamaDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'kategori_layanan'   => ($row['kategori_layanan'] ?? '') === 'Rawat Inap' ? 'rawat_inap' : 'rawat_jalan',
                        'jenis_layanan'      => $row['jenis_layanan'] ?? null,
                        'kepemilikan'        => $row['kepemilikan'] ?? 'Swasta',
                        'bpjs'               => ($row['bpjs'] ?? '') === 'Ya',
                        'bed_rawat_inap'     => (int) ($row['bed_rawat_inap'] ?? 0),
                        'jumlah_sdm'         => (int) ($row['jumlah_sdm'] ?? 0),
                        'ambulans_transport' => (int) ($row['ambulans_transport'] ?? 0),
                        'masa_izin'          => $row['masa_izin'] ?? null,
                        'pj'                 => $row['pj'] ?? null,
                        'kontak_pj'          => $row['kontak_pj'] ?? null,
                    ]
                );
                break;

            case 'klinik_utama':
                $faskes->klinikUtamaDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'kategori_layanan'   => ($row['kategori_layanan'] ?? '') === 'Rawat Inap' ? 'rawat_inap' : 'rawat_jalan',
                        'kemampuan_layanan'  => $row['kemampuan_layanan'] ?? null,
                        'kepemilikan'        => $row['kepemilikan'] ?? 'Swasta',
                        'bpjs'               => ($row['bpjs'] ?? '') === 'Ya',
                        'bed_rawat_inap'     => (int) ($row['bed_rawat_inap'] ?? 0),
                        'ambulans'           => (int) ($row['ambulans'] ?? 0),
                        'masa_izin'          => $row['masa_izin'] ?? null,
                        'pj'                 => $row['pj'] ?? null,
                        'kontak_pj'          => $row['kontak_pj'] ?? null,
                    ]
                );
                break;

            case 'laboratorium':
                $faskes->laboratoriumDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'jenis_layanan' => $row['jenis_layanan'] ?? null,
                        'kepemilikan'   => $row['kepemilikan'] ?? 'Swasta',
                        'masa_izin'     => $row['masa_izin'] ?? null,
                    ]
                );
                break;

            case 'upkdk':
                $faskes->upkdkDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'is_pustu'   => ($row['is_pustu'] ?? '') === 'Ya' ? 'Ya' : 'Tidak',
                        'is_pkd'     => ($row['is_pkd'] ?? '') === 'Ya' ? 'Ya' : 'Tidak',
                        'jumlah_sdm' => (int) ($row['jumlah_sdm'] ?? 0),
                    ]
                );
                break;

            case 'griya_sehat':
                $faskes->griyaSehatDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'masa_izin'  => $row['masa_izin'] ?? null,
                        'pj'         => $row['pj'] ?? null,
                        'kontak_pj'  => $row['kontak_pj'] ?? null,
                        'jumlah_sdm' => (int) ($row['jumlah_sdm'] ?? 0),
                    ]
                );
                break;
        }
    }

    /**
     * Parsing koordinat desimal secara ketat.
     */
    protected function parseCoordinateStrict(mixed $val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }
        $valStr = trim((string) $val);
        // Ubah koma desimal ke titik desimal
        $valStr = str_replace(',', '.', $valStr);

        // Jika angka tidak memiliki tanda titik desimal padahal angkanya sangat besar/panjang (misal -7424364),
        // ini adalah koordinat rusak tanpa desimal
        if (! str_contains($valStr, '.')) {
            // Kecuali nilai 0 persis
            if ($valStr !== '0') {
                return null;
            }
        }

        if (! is_numeric($valStr)) {
            return null;
        }

        return (float) $valStr;
    }

    /**
     * Parsing tanggal Excel / string secara ketat ke format YYYY-MM-DD.
     */
    protected function parseDateStrict(mixed $val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }

        // Jika berupa nilai serial angka tanggal Excel
        if (is_numeric($val)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $val);
                return $dt->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $str = trim((string) $val);

        // Coba format YYYY-MM-DD
        try {
            $parsed = Carbon::parse($str);
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
