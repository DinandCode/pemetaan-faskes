<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\IzinOperasionalService;

class DashboardController extends Controller
{
    /**
     * Versi cache untuk invalidate saat skema/logika berubah.
     */
    protected const CACHE_VERSION = 'v1';
    protected const CACHE_TTL_SECONDS = 60;

    /**
     * Menampilkan dashboard analitik dan ringkasan faskes.
     * Menggunakan render bertahap (Tugas 10A):
     * - Hanya menghitung KPI cards & ringkasan notifikasi izin di render awal (1-2 query cepat).
     * - Grafik dan tabel dimuat asynchronous/bertahap lewat endpoint JSON terpisah.
     * - Kompatibilitas mundur penuh: data grafik & top 5 izin tetap disediakan sebagai default
     *   sehingga script inline atau SSR tetap berjalan tanpa error.
     */
    public function index(Request $request, IzinOperasionalService $izinService)
    {
        $fresh = $request->boolean('fresh');

        // 1. KPI Cards (Agregasi 1 query gabungan efisien dengan caching 60s)
        $kpi = $this->getKpiSummaryCached($fresh);

        $totalFaskes     = $kpi['totalFaskes'];
        $totalPuskesmas  = $kpi['totalPuskesmas'];
        $totalRumahSakit = $kpi['totalRumahSakit'];
        $totalBed        = $kpi['totalBed'];
        $totalAmbulans   = $kpi['totalAmbulans'];

        // 2. Ringkasan Peringatan Izin Operasional untuk Badge Lonceng & Modal Notifikasi (Tugas 6)
        $ringkasanIzin  = $this->getRingkasanIzinCached($izinService, $fresh);
        $urgentIzinList = $this->getUrgentIzinListCached($izinService, $fresh);

        // 3. Fallback data awal untuk tabel 5 faskes & grafik agar view tetap kompatibel 100%
        // Nilai kosong ini akan diisi secara live & paralel oleh Alpine.js via AJAX
        $allWithIzin          = collect([]);
        $chartKecamatanLabels = [];
        $chartKecamatanData   = [];
        $chartKategoriLabels  = [];
        $chartKategoriData    = [];
        $chartKategoriColors  = [];

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
            'allWithIzin',
            'ringkasanIzin',
            'urgentIzinList'
        ));
    }

    /**
     * Endpoint JSON untuk data grafik kecamatan & kategori (Tugas 10A).
     */
    public function getChartsData(Request $request): JsonResponse
    {
        $fresh = $request->boolean('fresh');
        $cacheKey = 'dashboard_' . self::CACHE_VERSION . '_charts_data';

        $data = $this->rememberDatabaseCache($cacheKey, self::CACHE_TTL_SECONDS, function () {
            // A. Faskes per Kecamatan (Bar Chart)
            $faskesPerKecamatan = DB::table('faskes')
                ->select('kecamatan', DB::raw('count(*) as total'))
                ->whereNotNull('kecamatan')
                ->where('kecamatan', '!=', '')
                ->groupBy('kecamatan')
                ->orderBy('total', 'desc')
                ->get();

            $chartKecamatanLabels = $faskesPerKecamatan->pluck('kecamatan')->toArray();
            $chartKecamatanData   = array_map('intval', $faskesPerKecamatan->pluck('total')->toArray());

            // B. Persentase Kategori Faskes (Donut/Pie Chart)
            $faskesPerKategori = DB::table('faskes')
                ->select('jenis_faskes', DB::raw('count(*) as total'))
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
                'griya_sehat'    => 'Griya Sehat',
                'tpmd'           => 'TPMD',
                'tpmdg'          => 'TPMDG',
                'tpmb'           => 'TPMB',
                'tpmp'           => 'TPMP',
            ];

            $colorMap = [
                'rumah_sakit'    => '#ef4444',
                'puskesmas'      => '#3b82f6',
                'klinik_pratama' => '#10b981',
                'klinik_utama'   => '#14b8a6',
                'laboratorium'   => '#8b5cf6',
                'upkdk'          => '#f59e0b',
                'griya_sehat'    => '#06b6d4',
                'tpmd'           => '#0ea5e9',
                'tpmdg'          => '#6366f1',
                'tpmb'           => '#ec4899',
                'tpmp'           => '#84cc16',
            ];

            $chartKategoriLabels = [];
            $chartKategoriData   = [];
            $chartKategoriColors = [];

            foreach ($faskesPerKategori as $item) {
                $chartKategoriLabels[] = $kategoriMap[$item->jenis_faskes] ?? ucfirst($item->jenis_faskes);
                $chartKategoriData[]   = (int) $item->total;
                $chartKategoriColors[] = $colorMap[$item->jenis_faskes] ?? '#64748b';
            }

            return [
                'kecamatan' => [
                    'labels' => $chartKecamatanLabels,
                    'data'   => $chartKecamatanData,
                ],
                'kategori' => [
                    'labels' => $chartKategoriLabels,
                    'data'   => $chartKategoriData,
                    'colors' => $chartKategoriColors,
                ],
            ];
        }, $fresh);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Endpoint JSON untuk tabel 5 faskes dengan tanggal izin terdekat (Tugas 10A).
     */
    public function getTopIzinData(Request $request): JsonResponse
    {
        $fresh = $request->boolean('fresh');
        $cacheKey = 'dashboard_' . self::CACHE_VERSION . '_top_izin_data';

        $data = $this->rememberDatabaseCache($cacheKey, self::CACHE_TTL_SECONDS, function () {
            // Gunakan satu query LEFT JOIN langsung tanpa memuat relasi Eloquent atau memicu $appends N+1
            $rawList = DB::table('faskes as f')
                ->leftJoin('puskesmas_details as p', function ($j) {
                    $j->on('p.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'puskesmas');
                })
                ->leftJoin('rumah_sakit_details as rs', function ($j) {
                    $j->on('rs.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'rumah_sakit');
                })
                ->leftJoin('klinik_pratama_details as kp', function ($j) {
                    $j->on('kp.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'klinik_pratama');
                })
                ->leftJoin('klinik_utama_details as ku', function ($j) {
                    $j->on('ku.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'klinik_utama');
                })
                ->leftJoin('laboratorium_details as lab', function ($j) {
                    $j->on('lab.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'laboratorium');
                })
                ->leftJoin('griya_sehat_details as gs', function ($j) {
                    $j->on('gs.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'griya_sehat');
                })
                ->select([
                    'f.id',
                    'f.nama',
                    'f.jenis_faskes',
                    'f.kecamatan',
                    'f.desa',
                    'f.nomor_telepon',
                    DB::raw('COALESCE(p.masa_izin, rs.masa_izin, kp.masa_izin, ku.masa_izin, lab.masa_izin, gs.masa_izin) as masa_izin')
                ])
                ->whereRaw('COALESCE(p.masa_izin, rs.masa_izin, kp.masa_izin, ku.masa_izin, lab.masa_izin, gs.masa_izin) IS NOT NULL')
                ->orderBy('masa_izin', 'asc')
                ->limit(5)
                ->get();

            $now = Carbon::now();

            return $rawList->map(function ($row) use ($now) {
                $carbonDate = Carbon::parse($row->masa_izin);
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

                $badgeClasses = [
                    'rumah_sakit'    => 'bg-rose-50 text-rose-700 border-rose-200/60',
                    'puskesmas'      => 'bg-blue-50 text-blue-700 border-blue-200/60',
                    'klinik_pratama' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                    'klinik_utama'   => 'bg-teal-50 text-teal-700 border-teal-200/60',
                    'laboratorium'   => 'bg-purple-50 text-purple-700 border-purple-200/60',
                    'griya_sehat'    => 'bg-cyan-50 text-cyan-800 border-cyan-200/60',
                    'upkdk'          => 'bg-amber-50 text-amber-800 border-amber-200/60',
                ];

                $labels = [
                    'rumah_sakit'    => 'Rumah Sakit',
                    'puskesmas'      => 'Puskesmas',
                    'klinik_pratama' => 'Klinik Pratama',
                    'klinik_utama'   => 'Klinik Utama',
                    'laboratorium'   => 'Laboratorium',
                    'griya_sehat'    => 'Griya Sehat',
                    'upkdk'          => 'UPKDK',
                ];

                $statusPillClass = match ($statusBadge) {
                    'expired'  => 'bg-red-50 text-red-700 border-red-200 font-semibold',
                    'critical' => 'bg-rose-100/80 text-rose-800 border-rose-200 font-bold animate-pulse',
                    'warning'  => 'bg-amber-50 text-amber-800 border-amber-200 font-semibold',
                    default    => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-semibold',
                };

                return [
                    'id'                  => (int) $row->id,
                    'nama'                => $row->nama,
                    'jenis_faskes'        => $row->jenis_faskes,
                    'jenis_label'         => $labels[$row->jenis_faskes] ?? ucfirst(str_replace('_', ' ', $row->jenis_faskes)),
                    'badge_class'         => $badgeClasses[$row->jenis_faskes] ?? 'bg-slate-100 text-slate-700 border-slate-200',
                    'kecamatan'           => $row->kecamatan,
                    'desa'                => $row->desa,
                    'nomor_telepon'       => $row->nomor_telepon,
                    'masa_izin'           => $carbonDate->format('Y-m-d'),
                    'masa_izin_formatted' => $carbonDate->format('d M Y'),
                    'diff_days'           => $diffDays,
                    'status_badge'        => $statusBadge,
                    'status_text'         => $statusText,
                    'status_pill_class'   => $statusPillClass,
                    'edit_url'            => route('faskes.edit', $row->id),
                ];
            })->toArray();
        }, $fresh);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Endpoint API JSON untuk data pemantauan izin operasional terpaginasi (Tugas 6 & 10A).
     */
    public function getIzinOperasional(Request $request, IzinOperasionalService $izinService): JsonResponse
    {
        $filters = [
            'level'        => $request->query('level', 'all'),
            'jenis_faskes' => $request->query('jenis_faskes'),
            'kecamatan'    => $request->query('kecamatan'),
            'page'         => $request->query('page', 1),
            'per_page'     => $request->query('per_page', 10),
        ];

        $fresh = $request->boolean('fresh');
        $cacheKey = 'dashboard_' . self::CACHE_VERSION . '_izin_page_' . md5(json_encode($filters));

        $result = $this->rememberDatabaseCache($cacheKey, self::CACHE_TTL_SECONDS, function () use ($filters, $izinService) {
            return $izinService->getPagedData($filters);
        }, $fresh);

        return response()->json($result);
    }

    /**
     * Menghitung ringkasan KPI faskes dengan 1 query SQL efisien (PostgreSQL CTE).
     */
    protected function getKpiSummaryCached(bool $fresh = false): array
    {
        $cacheKey = 'dashboard_' . self::CACHE_VERSION . '_kpi_summary';

        return $this->rememberDatabaseCache($cacheKey, self::CACHE_TTL_SECONDS, function () {
            $row = DB::select("
                WITH faskes_summary AS (
                    SELECT 
                        count(*) as total_faskes,
                        count(*) FILTER (WHERE jenis_faskes = 'puskesmas') as total_puskesmas,
                        count(*) FILTER (WHERE jenis_faskes = 'rumah_sakit') as total_rs
                    FROM faskes
                ),
                resources_summary AS (
                    SELECT
                        COALESCE(SUM(bed_count), 0) as total_bed,
                        COALESCE(SUM(amb_count), 0) as total_ambulans
                    FROM (
                        SELECT jumlah_tempat_tidur as bed_count, 0 as amb_count FROM puskesmas_details
                        UNION ALL
                        SELECT bed_rawat_inap as bed_count, 0 as amb_count FROM klinik_pratama_details
                        UNION ALL
                        SELECT bed_rawat_inap as bed_count, 0 as amb_count FROM klinik_utama_details
                        UNION ALL
                        SELECT 0 as bed_count, (COALESCE(ambulans_transport, 0) + COALESCE(ambulans_roda_dua, 0)) as amb_count 
                        FROM puskesmas_details d JOIN faskes f ON f.id = d.faskes_id WHERE LOWER(f.status) = 'aktif'
                        UNION ALL
                        SELECT 0 as bed_count, (COALESCE(ambulans_transport, 0) + COALESCE(ambulans_gadar, 0)) as amb_count 
                        FROM rumah_sakit_details d JOIN faskes f ON f.id = d.faskes_id WHERE LOWER(f.status) = 'aktif'
                        UNION ALL
                        SELECT 0 as bed_count, COALESCE(ambulans_transport, 0) as amb_count 
                        FROM klinik_pratama_details d JOIN faskes f ON f.id = d.faskes_id WHERE LOWER(f.status) = 'aktif'
                        UNION ALL
                        SELECT 0 as bed_count, COALESCE(ambulans, 0) as amb_count 
                        FROM klinik_utama_details d JOIN faskes f ON f.id = d.faskes_id WHERE LOWER(f.status) = 'aktif'
                    ) sub
                )
                SELECT * FROM faskes_summary CROSS JOIN resources_summary
            ")[0];

            return [
                'totalFaskes'     => (int) $row->total_faskes,
                'totalPuskesmas'  => (int) $row->total_puskesmas,
                'totalRumahSakit' => (int) $row->total_rs,
                'totalBed'        => (int) $row->total_bed,
                'totalAmbulans'   => (int) $row->total_ambulans,
            ];
        }, $fresh);
    }

    /**
     * Mengambil ringkasan peringatan izin operasional dengan caching aman.
     */
    protected function getRingkasanIzinCached(IzinOperasionalService $izinService, bool $fresh = false): array
    {
        $cacheKey = 'dashboard_' . self::CACHE_VERSION . '_ringkasan_izin';

        return $this->rememberDatabaseCache($cacheKey, self::CACHE_TTL_SECONDS, function () use ($izinService) {
            return $izinService->getRingkasan();
        }, $fresh);
    }

    /**
     * Mengambil daftar mendesak izin operasional dengan caching aman.
     */
    protected function getUrgentIzinListCached(IzinOperasionalService $izinService, bool $fresh = false): array
    {
        $cacheKey = 'dashboard_' . self::CACHE_VERSION . '_urgent_izin_list';

        return $this->rememberDatabaseCache($cacheKey, self::CACHE_TTL_SECONDS, function () use ($izinService) {
            return $izinService->getUrgentList(10);
        }, $fresh);
    }

    /**
     * Helper Cache::store('database')->remember() dengan try/catch dan bypass parameter ?fresh=1 (Tugas 10A).
     */
    protected function rememberDatabaseCache(string $key, int $ttlSeconds, callable $callback, bool $fresh = false)
    {
        if ($fresh) {
            try {
                Cache::store('database')->forget($key);
            } catch (\Throwable $e) {
                // Jangan gagalkan request bila database cache bermasalah
            }
            return $callback();
        }

        try {
            return Cache::store('database')->remember($key, $ttlSeconds, $callback);
        } catch (\Throwable $e) {
            // Bila tabel cache/driver bermasalah, hitung langsung tanpa cache
            return $callback();
        }
    }
}
