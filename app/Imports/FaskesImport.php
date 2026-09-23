<?php

namespace App\Imports;

use App\Models\Faskes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FaskesImport implements ToCollection, WithHeadingRow
{
    /**
     * Counter untuk hasil import.
     */
    public int $importedCount = 0;
    public array $errors = [];

    /**
     * Memproses setiap baris dari sheet Excel rekapitulasi Dinkes.
     */
    public function collection(Collection $rows): void
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Baris Excel (header = baris 1)

                // Ambil & validasi nama faskes
                $nama = trim($row['nama'] ?? $row['nama_faskes'] ?? '');
                if (empty($nama)) {
                    continue; // Skip baris kosong
                }

                // Normalisasi jenis faskes
                $rawJenis = strtolower(trim($row['jenis_faskes'] ?? $row['jenis'] ?? 'puskesmas'));
                $jenisFaskes = $this->normalizeJenisFaskes($rawJenis);

                // Koordinat
                $lat = $this->parseCoordinate($row['latitude'] ?? $row['lat'] ?? null);
                $lng = $this->parseCoordinate($row['longitude'] ?? $row['lng'] ?? $row['long'] ?? null);

                if ($lat === null || $lng === null) {
                    $this->errors[] = "Baris {$rowNumber} ({$nama}): Koordinat latitude/longitude tidak valid.";
                    continue;
                }

                $alamat = $row['alamat'] ?? null;
                $kecamatan = $row['kecamatan'] ?? null;
                $desa = $row['desa'] ?? $row['kelurahan'] ?? null;
                $telepon = $row['nomor_telepon'] ?? $row['telepon'] ?? $row['telp'] ?? null;
                $status = strtolower(trim($row['status'] ?? 'aktif')) === 'nonaktif' ? 'nonaktif' : 'aktif';

                // Simpan atau update data Faskes utama
                $faskes = Faskes::updateOrCreate(
                    [
                        'nama'      => $nama,
                        'kecamatan' => $kecamatan,
                    ],
                    [
                        'jenis_faskes'  => $jenisFaskes,
                        'alamat'        => $alamat,
                        'desa'          => $desa,
                        'latitude'      => $lat,
                        'longitude'     => $lng,
                        'lokasi'        => DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)"),
                        'nomor_telepon' => $telepon,
                        'status'        => $status,
                    ]
                );

                // Simpan ke tabel detail child sesuai jenis
                $this->saveChildDetails($faskes, $row);

                $this->importedCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FaskesImport Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Normalisasi string jenis faskes ke format enum valid.
     */
    protected function normalizeJenisFaskes(string $raw): string
    {
        $cleaned = str_replace([' ', '-'], '_', $raw);

        if (str_contains($cleaned, 'rumah_sakit') || str_contains($cleaned, 'rsud') || str_contains($cleaned, 'rs')) {
            return 'rumah_sakit';
        }
        if (str_contains($cleaned, 'puskesmas') || str_contains($cleaned, 'pkm')) {
            return 'puskesmas';
        }
        if (str_contains($cleaned, 'klinik_utama')) {
            return 'klinik_utama';
        }
        if (str_contains($cleaned, 'klinik') || str_contains($cleaned, 'klinik_pratama')) {
            return 'klinik_pratama';
        }
        if (str_contains($cleaned, 'lab') || str_contains($cleaned, 'laboratorium')) {
            return 'laboratorium';
        }
        if (str_contains($cleaned, 'upkdk') || str_contains($cleaned, 'pustu') || str_contains($cleaned, 'pkd')) {
            return 'upkdk';
        }

        return 'puskesmas';
    }

    /**
     * Parsing nilai desimal koordinat.
     */
    protected function parseCoordinate(mixed $val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }
        // Ganti koma dengan titik jika format Indonesia
        $cleaned = str_replace(',', '.', trim((string) $val));
        if (! is_numeric($cleaned)) {
            return null;
        }
        return (float) $cleaned;
    }

    /**
     * Konversi string "ya"/"tidak", "1"/"0", "true"/"false" ke boolean.
     */
    protected function parseBoolean(mixed $val, bool $default = false): bool
    {
        if ($val === null || $val === '') {
            return $default;
        }
        $normalized = strtolower(trim((string) $val));
        return in_array($normalized, ['1', 'true', 'ya', 'yes', 'y', 'ada', 'on'], true);
    }

    /**
     * Konversi string/numeric ke integer dengan fallback boolean (1/0).
     */
    protected function parseInteger(mixed $val, int $default = 0): int
    {
        if ($val === null || $val === '') {
            return $default;
        }
        if (is_numeric($val)) {
            return (int) $val;
        }
        return $this->parseBoolean($val) ? 1 : 0;
    }

    /**
     * Menyimpan ke tabel child berdasarkan jenis faskes.
     */
    protected function saveChildDetails(Faskes $faskes, Collection $row): void
    {
        switch ($faskes->jenis_faskes) {
            case 'puskesmas':
                $kategori = strtolower(trim($row['kategori'] ?? 'rawat_jalan'));
                if (! in_array($kategori, ['rawat_jalan', 'rawat_inap'], true)) {
                    $kategori = 'rawat_jalan';
                }

                $wilayah = strtolower(trim($row['wilayah'] ?? 'perkotaan'));
                if (! in_array($wilayah, ['pedesaan', 'perkotaan'], true)) {
                    $wilayah = 'perkotaan';
                }

                $isPoned = $this->parseBoolean($row['poned'] ?? null);
                $isSalin = $this->parseBoolean($row['mampu_salin'] ?? null, true);

                $faskes->puskesmasDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'kategori'            => $kategori,
                        'poned'               => $isPoned ? 'Ya PONED' : 'Tidak PONED',
                        'mampu_salin'         => $isSalin ? 'Ya' : 'Tidak',
                        'jumlah_tempat_tidur' => (int) ($row['jumlah_tempat_tidur'] ?? $row['bed'] ?? 0),
                        'ambulans_transport'  => $this->parseInteger($row['ambulans_transport'] ?? $row['ambulans'] ?? null),
                        'ambulans_roda_dua'   => $this->parseInteger($row['ambulans_roda_dua'] ?? null),
                        'masa_izin'           => !empty($row['masa_izin']) ? $row['masa_izin'] : null,
                        'jumlah_sdm'          => (int) ($row['jumlah_sdm'] ?? $row['sdm'] ?? 0),
                        'wilayah'             => $wilayah,
                    ]
                );
                break;

            case 'rumah_sakit':
                $isPonek = $this->parseBoolean($row['ponek'] ?? null);

                $faskes->rumahSakitDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'ambulans_transport'  => $this->parseInteger($row['ambulans_transport'] ?? $row['ambulans'] ?? null, 1),
                        'ambulans_gadar'      => $this->parseInteger($row['ambulans_gadar'] ?? null, 1),
                        'ponek'               => $isPonek ? 'Ya PONEK' : 'Tidak PONEK',
                        'kemampuan_pelayanan' => $row['kemampuan_pelayanan'] ?? $row['tipe'] ?? null,
                        'masa_izin'           => !empty($row['masa_izin']) ? $row['masa_izin'] : null,
                    ]
                );
                break;

            case 'klinik_pratama':
                $faskes->klinikPratamaDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'ambulans_transport' => $this->parseInteger($row['ambulans_transport'] ?? $row['ambulans'] ?? null),
                        'masa_izin'          => !empty($row['masa_izin']) ? $row['masa_izin'] : null,
                        'jenis_layanan'      => $row['jenis_layanan'] ?? null,
                        'jumlah_sdm'         => (int) ($row['jumlah_sdm'] ?? $row['sdm'] ?? 0),
                        'bed_rawat_inap'     => (int) ($row['bed_rawat_inap'] ?? $row['bed'] ?? 0),
                        'bpjs'               => $this->parseBoolean($row['bpjs'] ?? null, true),
                        'pj'                 => $row['pj'] ?? $row['penanggung_jawab'] ?? null,
                        'kontak_pj'          => $row['kontak_pj'] ?? null,
                    ]
                );
                break;

            case 'klinik_utama':
                $faskes->klinikUtamaDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'ambulans'          => $this->parseInteger($row['ambulans'] ?? null),
                        'masa_izin'         => !empty($row['masa_izin']) ? $row['masa_izin'] : null,
                        'kemampuan_layanan' => $row['kemampuan_layanan'] ?? $row['layanan'] ?? null,
                        'kepemilikan'       => $row['kepemilikan'] ?? 'Swasta',
                    ]
                );
                break;

            case 'laboratorium':
                $faskes->laboratoriumDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'masa_izin'     => !empty($row['masa_izin']) ? $row['masa_izin'] : null,
                        'jenis_layanan' => $row['jenis_layanan'] ?? 'Laboratorium Medis',
                        'kepemilikan'   => $row['kepemilikan'] ?? 'Swasta',
                    ]
                );
                break;

            case 'upkdk':
                $jenisUpk = strtolower(trim($row['jenis_upkdk'] ?? $row['jenis'] ?? 'pustu'));
                if (! in_array($jenisUpk, ['pustu', 'pkd'], true)) {
                    $jenisUpk = 'pustu';
                }

                $isPustu = ($jenisUpk === 'pustu' || $this->parseBoolean($row['is_pustu'] ?? null)) ? 'Ya' : 'Tidak';
                $isPkd   = ($jenisUpk === 'pkd' || $this->parseBoolean($row['is_pkd'] ?? null)) ? 'Ya' : 'Tidak';

                $faskes->upkdkDetail()->updateOrCreate(
                    ['faskes_id' => $faskes->id],
                    [
                        'jenis'      => $jenisUpk,
                        'is_pustu'   => $isPustu,
                        'is_pkd'     => $isPkd,
                        'jumlah_sdm' => (int) ($row['jumlah_sdm'] ?? $row['sdm'] ?? 2),
                    ]
                );
                break;
        }
    }
}
