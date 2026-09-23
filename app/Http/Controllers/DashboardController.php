<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use App\Models\KlinikPratamaDetail;
use App\Models\PuskesmasDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard analitik dan ringkasan faskes.
     */
    public function index()
    {
        // 1. Ringkasan Kartu Statistik
        $totalFaskes = Faskes::count();
        $totalPuskesmas = Faskes::where('jenis_faskes', 'puskesmas')->count();
        $totalRumahSakit = Faskes::where('jenis_faskes', 'rumah_sakit')->count();

        // Total Bed Rawat Inap (Puskesmas + Klinik Pratama)
        $bedPuskesmas = (int) PuskesmasDetail::sum('jumlah_tempat_tidur');
        $bedKlinik = (int) KlinikPratamaDetail::sum('bed_rawat_inap');
        $totalBed = $bedPuskesmas + $bedKlinik;

        // Total Ambulans (Jumlah unit kendaraan dari semua faskes aktif)
        $ambPuskesmas = (int) PuskesmasDetail::whereHas('faskes', fn($q) => $q->where('status', 'aktif'))
            ->sum(DB::raw('COALESCE(ambulans_transport, 0) + COALESCE(ambulans_roda_dua, 0)'));
        $ambRs = (int) \App\Models\RumahSakitDetail::whereHas('faskes', fn($q) => $q->where('status', 'aktif'))
            ->sum(DB::raw('COALESCE(ambulans_transport, 0) + COALESCE(ambulans_gadar, 0)'));
        $ambKp = (int) KlinikPratamaDetail::whereHas('faskes', fn($q) => $q->where('status', 'aktif'))
            ->sum('ambulans_transport');
        $ambKu = (int) \App\Models\KlinikUtamaDetail::whereHas('faskes', fn($q) => $q->where('status', 'aktif'))
            ->sum('ambulans');
        $totalAmbulans = $ambPuskesmas + $ambRs + $ambKp + $ambKu;

        // 2. Data Grafik Chart.js
        // A. Faskes per Kecamatan (Bar Chart)
        $faskesPerKecamatan = Faskes::select('kecamatan', DB::raw('count(*) as total'))
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->groupBy('kecamatan')
            ->orderBy('total', 'desc')
            ->get();

        $chartKecamatanLabels = $faskesPerKecamatan->pluck('kecamatan')->toArray();
        $chartKecamatanData = $faskesPerKecamatan->pluck('total')->toArray();

        // B. Persentase Kategori Faskes (Donut/Pie Chart)
        $faskesPerKategori = Faskes::select('jenis_faskes', DB::raw('count(*) as total'))
            ->groupBy('jenis_faskes')
            ->orderBy('total', 'desc')
            ->get();

        $kategoriMap = [
            'rumah_sakit'    => 'Rumah Sakit',
            'puskesmas'      => 'Puskesmas',
            'klinik_pratama' => 'Klinik Pratama',
            'klinik_utama'   => 'Klinik Utama',
            'laboratorium'   => 'Laboratorium',
            'upkdk'          => 'UPKDK',
        ];

        $colorMap = [
            'rumah_sakit'    => '#ef4444', // Merah
            'puskesmas'      => '#3b82f6', // Biru
            'klinik_pratama' => '#10b981', // Hijau Emerald
            'klinik_utama'   => '#14b8a6', // Teal
            'laboratorium'   => '#8b5cf6', // Ungu
            'upkdk'          => '#f59e0b', // Amber/Kuning
        ];

        $chartKategoriLabels = [];
        $chartKategoriData = [];
        $chartKategoriColors = [];

        foreach ($faskesPerKategori as $item) {
            $chartKategoriLabels[] = $kategoriMap[$item->jenis_faskes] ?? ucfirst($item->jenis_faskes);
            $chartKategoriData[] = (int) $item->total;
            $chartKategoriColors[] = $colorMap[$item->jenis_faskes] ?? '#64748b';
        }

        // 3. Tabel Ringkasan: 5 Faskes dengan izin mendekati masa kadaluarsa
        $allWithIzin = Faskes::with([
            'puskesmasDetail',
            'rumahSakitDetail',
            'klinikPratamaDetail',
            'klinikUtamaDetail',
            'laboratoriumDetail',
        ])
        ->get()
        ->filter(function ($faskes) {
            return $faskes->detail && ! empty($faskes->detail->masa_izin);
        })
        ->map(function ($faskes) {
            $rawDate = $faskes->detail->masa_izin;
            $carbonDate = $rawDate instanceof Carbon ? $rawDate : Carbon::parse($rawDate);
            $now = Carbon::now();
            $diffDays = (int) $now->diffInDays($carbonDate, false);

            if ($diffDays < 0) {
                $statusBadge = 'expired';
                $statusText = 'Kadaluarsa (' . abs($diffDays) . ' hari lalu)';
            } elseif ($diffDays <= 180) {
                $statusBadge = 'critical';
                $statusText = 'Kritis (' . $diffDays . ' hari)';
            } elseif ($diffDays <= 365) {
                $statusBadge = 'warning';
                $statusText = 'Perlu Perpanjangan (' . $diffDays . ' hari)';
            } else {
                $statusBadge = 'safe';
                $statusText = 'Aktif (' . round($diffDays / 365, 1) . ' tahun)';
            }

            return [
                'faskes'       => $faskes,
                'masa_izin'    => $carbonDate,
                'diff_days'    => $diffDays,
                'status_badge' => $statusBadge,
                'status_text'  => $statusText,
            ];
        })
        ->sortBy('diff_days')
        ->take(5)
        ->values();

        return view('dashboard', compact(
            'totalFaskes',
            'totalPuskesmas',
            'totalRumahSakit',
            'totalBed',
            'totalAmbulans',
            'chartKecamatanLabels',
            'chartKecamatanData',
            'chartKategoriLabels',
            'chartKategoriData',
            'chartKategoriColors',
            'allWithIzin'
        ));
    }
}
