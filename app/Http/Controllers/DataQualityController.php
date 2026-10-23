<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use App\Services\IzinOperasionalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataQualityController extends Controller
{
    /**
     * Tampilkan halaman audit kualitas data faskes (READ-ONLY).
     */
    public function index(IzinOperasionalService $izinService)
    {
        // 1. Rekonsiliasi Status & Koordinat
        $totalMaster = Faskes::count();
        $totalAktif = Faskes::where('status', 'aktif')->count();
        $totalNonaktif = Faskes::where('status', 'nonaktif')->count();
        $faskesNonaktif = Faskes::where('status', 'nonaktif')
            ->select('id', 'nama', 'jenis_faskes', 'kecamatan', 'desa', 'latitude', 'longitude')
            ->orderBy('id')
            ->get();

        // 2. Faskes di luar kotak batas Banyumas (-7.75 s/d -7.15, 108.95 s/d 109.50)
        $luarBanyumas = Faskes::where('status', 'aktif')
            ->where(function ($q) {
                $q->where('latitude', '<', -7.75)
                  ->orWhere('latitude', '>', -7.15)
                  ->orWhere('longitude', '<', 108.95)
                  ->orWhere('longitude', '>', 109.50);
            })
            ->select('id', 'nama', 'jenis_faskes', 'kecamatan', 'desa', 'latitude', 'longitude')
            ->get();

        // 3. Kecamatan Tidak Baku (di luar 27 kecamatan resmi Banyumas)
        $officialKecamatan = [
            'Ajibarang', 'Banyumas', 'Baturraden', 'Cilongok', 'Gumelar', 'Jatilawang',
            'Kalibagor', 'Karanglewas', 'Kebasen', 'Kedungbanteng', 'Kembaran', 'Kemranjen',
            'Lumbir', 'Patikraja', 'Pekuncen', 'Purwojati', 'Purwokerto Barat',
            'Purwokerto Selatan', 'Purwokerto Timur', 'Purwokerto Utara', 'Rawalo',
            'Sokaraja', 'Somagede', 'Sumbang', 'Sumpiuh', 'Tambak', 'Wangon'
        ];

        $kecamatanAnomali = Faskes::whereNotIn('kecamatan', $officialKecamatan)
            ->orWhereNull('kecamatan')
            ->select('id', 'nama', 'jenis_faskes', 'kecamatan', 'desa')
            ->orderBy('kecamatan')
            ->get();

        // 4. Duplikasi Persis (Nama + Jenis + Kecamatan)
        $duplikatData = DB::table('faskes')
            ->select('nama', 'jenis_faskes', 'kecamatan', DB::raw('COUNT(*) as total_kembar'), DB::raw('array_agg(id) as ids'))
            ->groupBy('nama', 'jenis_faskes', 'kecamatan')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        // 5. Ringkasan Izin Operasional
        $ringkasanIzin = $izinService->getRingkasan();

        // 6. Tanggal Izin Masa Lampau Ekstrem (> 5 tahun lalu)
        $izinLampauEkstrem = DB::table('faskes as f')
            ->join('rumah_sakit_details as rs', 'rs.faskes_id', '=', 'f.id')
            ->where('rs.masa_izin', '<', '2020-01-01')
            ->select('f.id', 'f.nama', 'f.jenis_faskes', 'f.status', 'rs.masa_izin')
            ->get();

        // 7. Inkonsistensi Lintas Kolom (Kategori vs Bed)
        $inkonsistensiPuskesmas = DB::table('faskes as f')
            ->join('puskesmas_details as p', 'p.faskes_id', '=', 'f.id')
            ->where('p.kategori', 'non_rawat_inap')
            ->where('p.jumlah_tempat_tidur', '>', 0)
            ->select('f.id', 'f.nama', 'p.kategori', 'p.jumlah_tempat_tidur')
            ->get();

        $inkonsistensiKlinik = DB::table('faskes as f')
            ->join('klinik_pratama_details as kp', 'kp.faskes_id', '=', 'f.id')
            ->where('kp.kategori_layanan', 'rawat_inap')
            ->where(function ($q) {
                $q->where('kp.bed_rawat_inap', '<=', 0)
                  ->orWhereNull('kp.bed_rawat_inap');
            })
            ->select('f.id', 'f.nama', 'kp.kategori_layanan', 'kp.bed_rawat_inap as jumlah_tempat_tidur')
            ->get();

        return view('data-quality', compact(
            'totalMaster',
            'totalAktif',
            'totalNonaktif',
            'faskesNonaktif',
            'luarBanyumas',
            'kecamatanAnomali',
            'duplikatData',
            'ringkasanIzin',
            'izinLampauEkstrem',
            'inkonsistensiPuskesmas',
            'inkonsistensiKlinik'
        ));
    }
}
