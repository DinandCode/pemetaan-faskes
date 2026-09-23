<?php

namespace Database\Seeders;

use App\Models\Faskes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaskesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bersihkan data faskes sebelumnya (child detail otomatis terhapus via CASCADE)
        Faskes::query()->delete();

        // ==========================================
        // 1. RUMAH SAKIT (KABUPATEN BANYUMAS)
        // ==========================================
        $rs1 = Faskes::create([
            'nama'          => 'RSUD Prof. Dr. Margono Soekarjo',
            'jenis_faskes'  => 'rumah_sakit',
            'alamat'        => 'Jl. Dr. Gumbreg No. 1, Mersi',
            'kecamatan'     => 'Purwokerto Selatan',
            'desa'          => 'Berkoh',
            'latitude'      => -7.438500,
            'longitude'     => 109.261800,
            'nomor_telepon' => '0281-632708',
            'status'        => 'aktif',
        ]);
        $rs1->rumahSakitDetail()->create([
            'ambulans_transport' => 4,
            'ambulans_gadar'     => 2,
            'ponek'              => 'Ya PONEK',
            'kemampuan_pelayanan'=> 'Rumah Sakit Rujukan Utama Regional Jawa Tengah Bagian Selatan (Tipe A)',
            'masa_izin'          => '2030-12-31',
        ]);

        $rs2 = Faskes::create([
            'nama'          => 'RSUD Banyumas',
            'jenis_faskes'  => 'rumah_sakit',
            'alamat'        => 'Jl. Rumah Sakit No. 1',
            'kecamatan'     => 'Banyumas',
            'desa'          => 'Sudagaran',
            'latitude'      => -7.514700,
            'longitude'     => 109.294200,
            'nomor_telepon' => '0281-796031',
            'status'        => 'aktif',
        ]);
        $rs2->rumahSakitDetail()->create([
            'ambulans_transport' => 3,
            'ambulans_gadar'     => 2,
            'ponek'              => 'Ya PONEK',
            'kemampuan_pelayanan'=> 'Rumah Sakit Umum Daerah Tipe B Pendidikan',
            'masa_izin'          => '2029-08-20',
        ]);

        $rs3 = Faskes::create([
            'nama'          => 'RS Wijayakusuma (DKT Purwokerto)',
            'jenis_faskes'  => 'rumah_sakit',
            'alamat'        => 'Jl. Prof. Dr. HR Boenyamin No. 1',
            'kecamatan'     => 'Purwokerto Timur',
            'desa'          => 'Sokanegara',
            'latitude'      => -7.419200,
            'longitude'     => 109.238500,
            'nomor_telepon' => '0281-635201',
            'status'        => 'aktif',
        ]);
        $rs3->rumahSakitDetail()->create([
            'ambulans_transport' => 2,
            'ambulans_gadar'     => 1,
            'ponek'              => 'Tidak PONEK',
            'kemampuan_pelayanan'=> 'Rumah Sakit Tingkat III Tipe C',
            'masa_izin'          => '2028-05-15',
        ]);

        $rs4 = Faskes::create([
            'nama'          => 'RSUD Ajibarang',
            'jenis_faskes'  => 'rumah_sakit',
            'alamat'        => 'Jl. Raya Pancasan No. 1',
            'kecamatan'     => 'Ajibarang',
            'desa'          => 'Pancasan',
            'latitude'      => -7.424600,
            'longitude'     => 109.076800,
            'nomor_telepon' => '0281-6570004',
            'status'        => 'aktif',
        ]);
        $rs4->rumahSakitDetail()->create([
            'ambulans_transport' => 2,
            'ambulans_gadar'     => 1,
            'ponek'              => 'Ya PONEK',
            'kemampuan_pelayanan'=> 'Rumah Sakit Umum Daerah Wilayah Barat Tipe C',
            'masa_izin'          => '2029-10-10',
        ]);

        // ==========================================
        // 2. PUSKESMAS (KABUPATEN BANYUMAS)
        // ==========================================
        $pkm1 = Faskes::create([
            'nama'          => 'Puskesmas 1 Purwokerto Timur',
            'jenis_faskes'  => 'puskesmas',
            'alamat'        => 'Jl. Adipati Mersi No. 12',
            'kecamatan'     => 'Purwokerto Timur',
            'desa'          => 'Mersi',
            'latitude'      => -7.426100,
            'longitude'     => 109.252300,
            'nomor_telepon' => '0281-638765',
            'status'        => 'aktif',
        ]);
        $pkm1->puskesmasDetail()->create([
            'kategori'            => 'rawat_inap',
            'poned'               => 'Ya PONED',
            'mampu_salin'         => 'Ya',
            'jumlah_tempat_tidur' => 12,
            'ambulans_transport'  => 1,
            'ambulans_roda_dua'   => 1,
            'masa_izin'           => '2028-06-30',
            'jumlah_sdm'          => 32,
            'wilayah'             => 'perkotaan',
        ]);

        $pkm2 = Faskes::create([
            'nama'          => 'Puskesmas 1 Purwokerto Selatan',
            'jenis_faskes'  => 'puskesmas',
            'alamat'        => 'Jl. Wahid Hasyim No. 45',
            'kecamatan'     => 'Purwokerto Selatan',
            'desa'          => 'Karangklesem',
            'latitude'      => -7.445200,
            'longitude'     => 109.236700,
            'nomor_telepon' => '0281-684432',
            'status'        => 'aktif',
        ]);
        $pkm2->puskesmasDetail()->create([
            'kategori'            => 'rawat_jalan',
            'poned'               => 'Tidak PONED',
            'mampu_salin'         => 'Ya',
            'jumlah_tempat_tidur' => 4,
            'ambulans_transport'  => 1,
            'ambulans_roda_dua'   => 1,
            'masa_izin'           => '2027-11-20',
            'jumlah_sdm'          => 24,
            'wilayah'             => 'perkotaan',
        ]);

        $pkm3 = Faskes::create([
            'nama'          => 'Puskesmas Baturraden 1',
            'jenis_faskes'  => 'puskesmas',
            'alamat'        => 'Jl. Raya Baturraden Km. 7',
            'kecamatan'     => 'Baturraden',
            'desa'          => 'Rempoah',
            'latitude'      => -7.351200,
            'longitude'     => 109.222500,
            'nomor_telepon' => '0281-681812',
            'status'        => 'aktif',
        ]);
        $pkm3->puskesmasDetail()->create([
            'kategori'            => 'rawat_inap',
            'poned'               => 'Ya PONED',
            'mampu_salin'         => 'Ya',
            'jumlah_tempat_tidur' => 10,
            'ambulans_transport'  => 1,
            'ambulans_roda_dua'   => 0,
            'masa_izin'           => '2028-09-15',
            'jumlah_sdm'          => 26,
            'wilayah'             => 'pedesaan',
        ]);

        $pkm4 = Faskes::create([
            'nama'          => 'Puskesmas Sokaraja 1',
            'jenis_faskes'  => 'puskesmas',
            'alamat'        => 'Jl. Jend. Soedirman No. 78',
            'kecamatan'     => 'Sokaraja',
            'desa'          => 'Sokaraja Tengah',
            'latitude'      => -7.458900,
            'longitude'     => 109.278500,
            'nomor_telepon' => '0281-644123',
            'status'        => 'aktif',
        ]);
        $pkm4->puskesmasDetail()->create([
            'kategori'            => 'rawat_inap',
            'poned'               => 'Ya PONED',
            'mampu_salin'         => 'Ya',
            'jumlah_tempat_tidur' => 14,
            'ambulans_transport'  => 2,
            'ambulans_roda_dua'   => 1,
            'masa_izin'           => '2029-01-31',
            'jumlah_sdm'          => 30,
            'wilayah'             => 'perkotaan',
        ]);

        // ==========================================
        // 3. KLINIK PRATAMA (KABUPATEN BANYUMAS)
        // ==========================================
        $kp1 = Faskes::create([
            'nama'          => 'Klinik Pratama Rawat Inap PMI Banyumas',
            'jenis_faskes'  => 'klinik_pratama',
            'alamat'        => 'Jl. Raya Kalibagor No. 5',
            'kecamatan'     => 'Sokaraja',
            'desa'          => 'Karangduren',
            'latitude'      => -7.452300,
            'longitude'     => 109.267800,
            'nomor_telepon' => '0281-644556',
            'status'        => 'aktif',
        ]);
        $kp1->klinikPratamaDetail()->create([
            'ambulans_transport' => 2,
            'masa_izin'          => '2028-04-10',
            'jenis_layanan'      => 'Pelayanan 24 Jam, Poli Umum, Rawat Inap Pratama, Bank Darah',
            'jumlah_sdm'         => 18,
            'bed_rawat_inap'     => 8,
            'bpjs'               => true,
            'pj'                 => 'dr. Tri Haryanto',
            'kontak_pj'          => '08122334455',
        ]);

        // ==========================================
        // 4. KLINIK UTAMA (KABUPATEN BANYUMAS)
        // ==========================================
        $ku1 = Faskes::create([
            'nama'          => 'Klinik Utama Mata Purwokerto',
            'jenis_faskes'  => 'klinik_utama',
            'alamat'        => 'Jl. Gatot Subroto No. 65',
            'kecamatan'     => 'Purwokerto Timur',
            'desa'          => 'Kranji',
            'latitude'      => -7.422500,
            'longitude'     => 109.233400,
            'nomor_telepon' => '0281-638899',
            'status'        => 'aktif',
        ]);
        $ku1->klinikUtamaDetail()->create([
            'ambulans'           => 1,
            'masa_izin'          => '2029-03-20',
            'kemampuan_layanan'  => 'Poli Spesialis Mata, Bedah Katarak Modern Phacoemulsifikasi, Glaukoma Center',
            'kepemilikan'        => 'Swasta',
        ]);

        // ==========================================
        // 5. LABORATORIUM (KABUPATEN BANYUMAS)
        // ==========================================
        $lab1 = Faskes::create([
            'nama'          => 'Laboratorium Klinik Prodia Purwokerto',
            'jenis_faskes'  => 'laboratorium',
            'alamat'        => 'Jl. Jenderal Sudirman No. 423',
            'kecamatan'     => 'Purwokerto Timur',
            'desa'          => 'Sokanegara',
            'latitude'      => -7.427800,
            'longitude'     => 109.239200,
            'nomor_telepon' => '0281-636688',
            'status'        => 'aktif',
        ]);
        $lab1->laboratoriumDetail()->create([
            'masa_izin'     => '2030-07-15',
            'jenis_layanan' => 'Pemeriksaan Darah Lengkap, Panel Check-Up Rutin, Mikrobiologi, Tes Alergi',
            'kepemilikan'   => 'PT Prodia Widyahusada Tbk',
        ]);

        // ==========================================
        // 6. UPKDK (PUSTU / PKD KABUPATEN BANYUMAS)
        // ==========================================
        $upk1 = Faskes::create([
            'nama'          => 'UPKDK Pustu Karangcegak',
            'jenis_faskes'  => 'upkdk',
            'alamat'        => 'Jl. Raya Karangcegak No. 12',
            'kecamatan'     => 'Sumbang',
            'desa'          => 'Karangcegak',
            'latitude'      => -7.378500,
            'longitude'     => 109.255400,
            'nomor_telepon' => '0281-689123',
            'status'        => 'aktif',
        ]);
        $upk1->upkdkDetail()->create([
            'jenis'      => 'pustu',
            'is_pustu'   => 'Ya',
            'is_pkd'     => 'Tidak',
            'jumlah_sdm' => 4,
        ]);

        $upk2 = Faskes::create([
            'nama'          => 'UPKDK Pos Kesehatan Desa (PKD) Kalisube',
            'jenis_faskes'  => 'upkdk',
            'alamat'        => 'Jl. Balai Desa Kalisube RT 02/01',
            'kecamatan'     => 'Banyumas',
            'desa'          => 'Kalisube',
            'latitude'      => -7.525400,
            'longitude'     => 109.288700,
            'nomor_telepon' => '0281-796789',
            'status'        => 'aktif',
        ]);
        $upk2->upkdkDetail()->create([
            'jenis'      => 'pkd',
            'is_pustu'   => 'Tidak',
            'is_pkd'     => 'Ya',
            'jumlah_sdm' => 2,
        ]);
    }
}
