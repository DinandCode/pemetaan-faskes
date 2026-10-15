<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use App\Services\OsrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalisisEventController extends Controller
{
    /**
     * Batas pengaman teknis maksimal faskes yang ditampilkan dalam hasil analisis event.
     */
    public const MAX_RESULTS = 300;

    /**
     * Ukuran batch destinasi per panggilan OSRM Table.
     */
    public const CHUNK_SIZE = 50;

    /**
     * Jumlah faskes peringkat teratas dalam urutan final yang langsung dihitung geometri garis rutenya.
     */
    public const TOP_ROUTE_GEOMETRY = 5;

    /**
     * Pemetaan prioritas jenis faskes (Tier 1: RS, Tier 2: PKM, Tier 3: Lainnya).
     */
    public const TIER_MAP = [
        'rumah_sakit'    => ['tier' => 1, 'label' => 'Rumah Sakit'],
        'puskesmas'      => ['tier' => 2, 'label' => 'Puskesmas'],
        'klinik_pratama' => ['tier' => 3, 'label' => 'Faskes lainnya'],
        'klinik_utama'   => ['tier' => 3, 'label' => 'Faskes lainnya'],
        'laboratorium'   => ['tier' => 3, 'label' => 'Faskes lainnya'],
        'upkdk'          => ['tier' => 3, 'label' => 'Faskes lainnya'],
        'griya_sehat'    => ['tier' => 3, 'label' => 'Faskes lainnya'],
        'tpmd'           => ['tier' => 3, 'label' => 'Faskes lainnya'],
        'tpmdg'          => ['tier' => 3, 'label' => 'Faskes lainnya'],
        'tpmb'           => ['tier' => 3, 'label' => 'Faskes lainnya'],
        'tpmp'           => ['tier' => 3, 'label' => 'Faskes lainnya'],
    ];

    /**
     * Service OSRM untuk kalkulasi rute jalan nyata.
     */
    protected OsrmService $osrmService;

    public function __construct(OsrmService $osrmService)
    {
        $this->osrmService = $osrmService;
    }

    /**
     * Endpoint aditif untuk mengambil geometri dan rute faskes tunggal secara malas (lazy loading).
     * GET /api/analisis-event/rute?latitude=&longitude=&faskes_id=
     */
    public function rute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'faskes_id' => 'required|integer',
        ]);

        $eventLat = (float) $validated['latitude'];
        $eventLng = (float) $validated['longitude'];

        $faskes = Faskes::query()
            ->where('id', $validated['faskes_id'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first();

        if (! $faskes) {
            return response()->json([
                'success' => false,
                'message' => 'Fasilitas kesehatan tidak ditemukan atau tidak memiliki koordinat valid.',
            ], 404);
        }

        $routeResult = $this->osrmService->getRoute(
            $eventLat,
            $eventLng,
            (float) $faskes->latitude,
            (float) $faskes->longitude
        );

        $ruteTersedia = (bool) $routeResult['success'];

        return response()->json([
            'success'           => true,
            'faskes_id'         => $faskes->id,
            'rute_tersedia'     => $ruteTersedia,
            'pesan_rute'        => $ruteTersedia ? null : $routeResult['message'],
            'titik_rute_jalan'  => $routeResult['geometry'] ?? null,
            'jarak_jalan'       => [
                'meter' => $ruteTersedia ? $routeResult['distance_meters'] : null,
                'km'    => $ruteTersedia ? $routeResult['distance_km'] : null,
            ],
            'estimasi_waktu'    => [
                'detik' => $ruteTersedia ? $routeResult['duration_seconds'] : null,
                'menit' => $ruteTersedia ? $routeResult['duration_minutes'] : null,
            ],
            'geometri_tersedia' => $ruteTersedia && ! empty($routeResult['geometry']),
        ]);
    }

    /**
     * Menganalisis faskes terdekat dari koordinat event berdasarkan radius (PostGIS ST_DistanceSphere),
     * menghitung jarak rute jalan & estimasi waktu tempuh batch (OSRM Table), dan mengembalikan data analisis lengkap.
     */
    public function analisis(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'        => 'required|numeric|between:-90,90',
            'longitude'       => 'required|numeric|between:-180,180',
            'radius_km'       => 'nullable|numeric|min:0.1|max:100',
            'jenis_faskes'    => 'nullable|array',
            'jenis_faskes.*'  => 'string|in:puskesmas,rumah_sakit,klinik_pratama,klinik_utama,laboratorium,upkdk,griya_sehat,tpmd,tpmdg,tpmb,tpmp',
            'limit'           => 'nullable|integer|min:1',
            'sort_by'         => 'nullable|string|in:jarak_jalan,jarak_lurus,estimasi_waktu',
            'prioritas_jenis' => 'nullable|boolean',
            'has_ambulans'    => 'nullable|boolean',
            'has_bpjs'        => 'nullable|boolean',
            'has_rawat_inap'  => 'nullable|boolean',
            'has_poned'       => 'nullable|boolean',
            // Filter Atribut Lanjutan (Tugas 4)
            'min_total_ambulans'     => 'nullable|integer|min:0|max:100',
            'min_ambulans_gadar'     => 'nullable|integer|min:0|max:50',
            'min_ambulans_roda_dua'  => 'nullable|integer|min:0|max:50',
            'min_bed'                => 'nullable|integer|min:0|max:500',
            'min_sdm'                => 'nullable|integer|min:0|max:1000',
            'kepemilikan'            => 'nullable|string|in:Swasta,Pemerintah',
            'tipe_rs'                => 'nullable|string|in:A,B,C,D,D Pratama',
            'kategori_puskesmas'     => 'nullable|string|in:rawat_inap,rawat_jalan',
            'wilayah_puskesmas'      => 'nullable|string|in:perkotaan,pedesaan',
            'persalinan_puskesmas'   => 'nullable|string|in:poned,mampu_salin,tidak_mampu_salin',
            'ponek_rs'               => 'nullable|string',
            'kategori_layanan_klinik' => 'nullable|string|in:rawat_jalan,rawat_inap',
            'bpjs_klinik'            => 'nullable|string',
            'status_izin'            => 'nullable|string|in:berlaku,berakhir_6_bulan,berakhir_3_bulan,berakhir_1_bulan,kedaluwarsa,belum_diisi',
            'custom_filter'          => 'nullable|array',
        ]);

        $eventLat = (float) $validated['latitude'];
        $eventLng = (float) $validated['longitude'];
        $radiusKm = isset($validated['radius_km']) ? (float) $validated['radius_km'] : 5.0;
        $limit = isset($validated['limit']) ? (int) $validated['limit'] : null;
        $sortBy = $validated['sort_by'] ?? 'jarak_jalan';
        $prioritasJenis = $request->has('prioritas_jenis') ? $request->boolean('prioritas_jenis') : true;

        // 1. Query PostGIS ST_DistanceSphere untuk menyaring faskes dalam radius
        $query = Faskes::query()
            ->where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with([
                'puskesmasDetail',
                'rumahSakitDetail',
                'klinikPratamaDetail',
                'klinikUtamaDetail',
                'laboratoriumDetail',
                'upkdkDetail',
                'griyaSehatDetail',
                'tpmDetail',
            ])
            ->withDistance($eventLat, $eventLng)
            ->withinRadius($eventLat, $eventLng, $radiusKm);

        if (! empty($validated['jenis_faskes'])) {
            $jenis = array_filter($validated['jenis_faskes']);
            if (! empty($jenis)) {
                $query->whereIn('jenis_faskes', $jenis);
            }
        }

        // Filter Spesifik whereHas
        if ($request->boolean('has_ambulans')) {
            $query->where(function ($q) {
                $q->whereHas('puskesmasDetail', function ($sub) {
                    $sub->where('ambulans_transport', '>', 0)->orWhere('ambulans_roda_dua', '>', 0);
                })->orWhereHas('rumahSakitDetail', function ($sub) {
                    $sub->where('ambulans_gadar', '>', 0)->orWhere('ambulans_transport', '>', 0);
                })->orWhereHas('klinikPratamaDetail', function ($sub) {
                    $sub->where('ambulans_transport', '>', 0);
                })->orWhereHas('klinikUtamaDetail', function ($sub) {
                    $sub->where('ambulans', '>', 0);
                });
            });
        }

        if ($request->boolean('has_bpjs')) {
            $query->where(function ($q) {
                $q->whereHas('klinikPratamaDetail', function ($sub) {
                    $sub->where('bpjs', true);
                })->orWhereHas('klinikUtamaDetail', function ($sub) {
                    $sub->where('bpjs', true);
                })->orWhereIn('jenis_faskes', ['puskesmas', 'rumah_sakit']);
            });
        }

        if ($request->boolean('has_rawat_inap')) {
            $query->where(function ($q) {
                $q->where('jenis_faskes', 'rumah_sakit')
                    ->orWhereHas('puskesmasDetail', function ($sub) {
                        $sub->where('kategori', 'rawat_inap')->orWhere('jumlah_tempat_tidur', '>', 0);
                    })->orWhereHas('klinikPratamaDetail', function ($sub) {
                        $sub->where('bed_rawat_inap', '>', 0)->orWhere('kategori_layanan', 'rawat_inap');
                    })->orWhereHas('klinikUtamaDetail', function ($sub) {
                        $sub->where('bed_rawat_inap', '>', 0)->orWhere('kategori_layanan', 'rawat_inap');
                    });
            });
        }

        if ($request->boolean('has_poned')) {
            $query->whereHas('puskesmasDetail', function ($sub) {
                $sub->where('poned', 'Ya PONED');
            });
        }

        // Filter Atribut Lanjutan (Tugas 4: Jumlah, Kategori, Izin Operasional, Custom Fields)
        \App\Support\FaskesAttributeFilter::apply($query, $request);

        // Ambil total faskes dalam radius tanpa batasan cap MAX_RESULTS
        $totalDalamRadius = (clone $query)->count();

        // Urut awal: jika prioritas aktif, urutkan tier dulu di database lalu jarak lurus
        if ($prioritasJenis) {
            $query->orderByRaw("
                CASE
                    WHEN jenis_faskes = 'rumah_sakit' THEN 1
                    WHEN jenis_faskes = 'puskesmas' THEN 2
                    ELSE 3
                END ASC
            ");
        }
        $query->orderBy('jarak_lurus_meter', 'asc');

        // Terapkan batas pengaman teknis MAX_RESULTS
        $kandidatFaskes = $query->limit(self::MAX_RESULTS)->get();
        $dibatasi = $totalDalamRadius > self::MAX_RESULTS;

        // 2. Hitung jarak & durasi jalan untuk SEMUA kandidat menggunakan OSRM Table dalam batch CHUNK_SIZE
        $destinations = $kandidatFaskes->map(fn (Faskes $f) => [
            'id'  => $f->id,
            'lat' => (float) $f->latitude,
            'lng' => (float) $f->longitude,
        ])->values()->all();

        $chunks = array_chunk($destinations, self::CHUNK_SIZE);
        $tableResults = [];

        foreach ($chunks as $chunk) {
            $res = $this->osrmService->getTable($eventLat, $eventLng, $chunk);
            if ($res['success']) {
                foreach ($res['results'] as $idx => $r) {
                    $tableResults[] = $r;
                }
            } else {
                // Fallback graceful jika batch Table gagal
                foreach ($chunk as $ignored) {
                    $tableResults[] = [
                        'success'                   => false,
                        'distance_meters'           => null,
                        'duration_seconds'          => null,
                        'distance_km'               => null,
                        'duration_minutes'          => null,
                        'snap_distance_dest_meters' => null,
                        'message'                   => 'Jarak jalan tidak tersedia (' . ($res['message'] ?? 'layanan rute OSRM offline') . ')',
                    ];
                }
            }
        }

        // 3. Susun item hasil analisis awal
        $hasilAnalisis = $kandidatFaskes->values()->map(function (Faskes $faskes, int $i) use ($tableResults) {
            $jarakLurusMeter = round((float) $faskes->jarak_lurus_meter, 2);
            $jarakLurusKm = round($jarakLurusMeter / 1000, 2);

            $tbl = $tableResults[$i] ?? [
                'success'                   => false,
                'distance_meters'           => null,
                'duration_seconds'          => null,
                'distance_km'               => null,
                'duration_minutes'          => null,
                'snap_distance_dest_meters' => null,
                'message'                   => 'Jarak jalan tidak tersedia',
            ];

            $ruteTersedia = (bool) ($tbl['success'] ?? false);
            $tierInfo = self::TIER_MAP[$faskes->jenis_faskes] ?? ['tier' => 3, 'label' => 'Faskes lainnya'];

            return [
                'detail_faskes' => [
                    'id'            => $faskes->id,
                    'nama'          => $faskes->nama,
                    'jenis_faskes'  => $faskes->jenis_faskes,
                    'alamat'        => $faskes->alamat,
                    'kecamatan'     => $faskes->kecamatan,
                    'desa'          => $faskes->desa,
                    'latitude'      => (float) $faskes->latitude,
                    'longitude'     => (float) $faskes->longitude,
                    'nomor_telepon' => $faskes->nomor_telepon,
                    'status'        => $faskes->status,
                    'spesifikasi'   => $faskes->detail,
                ],
                'prioritas_tier'  => $tierInfo['tier'],
                'prioritas_label' => $tierInfo['label'],
                'jarak_lurus'     => [
                    'meter' => $jarakLurusMeter,
                    'km'    => $jarakLurusKm,
                ],
                'jarak_jalan'     => [
                    'meter' => $ruteTersedia ? $tbl['distance_meters'] : null,
                    'km'    => $ruteTersedia ? $tbl['distance_km'] : null,
                ],
                'estimasi_waktu'  => [
                    'detik' => $ruteTersedia ? $tbl['duration_seconds'] : null,
                    'menit' => $ruteTersedia ? $tbl['duration_minutes'] : null,
                ],
                'titik_rute_jalan'  => null,
                'rute_tersedia'     => $ruteTersedia,
                'pesan_rute'        => $ruteTersedia ? null : ($tbl['message'] ?? 'Jarak jalan tidak tersedia'),
                'geometri_tersedia' => false,
            ];
        });

        // 4. Pengurutan: Dukung Prioritas Jenis (Tier) + Sort By, dengan fallback rute gagal di akhir tier
        $hasilSorted = $hasilAnalisis->sort(function ($a, $b) use ($sortBy, $prioritasJenis) {
            // Level 1: Urut Tier jika prioritas_jenis aktif
            if ($prioritasJenis) {
                $tierCmp = ($a['prioritas_tier'] <=> $b['prioritas_tier']);
                if ($tierCmp !== 0) {
                    return $tierCmp;
                }
            }

            // Level 2: Faskes dengan rute_tersedia = true didahulukan dibanding yang rutenya gagal
            $ruteCmp = ($a['rute_tersedia'] ? 0 : 1) <=> ($b['rute_tersedia'] ? 0 : 1);
            if ($ruteCmp !== 0) {
                return $ruteCmp;
            }

            // Level 3: Urut sesuai $sortBy
            if ($sortBy === 'estimasi_waktu') {
                if ($a['rute_tersedia'] && $b['rute_tersedia']) {
                    $durCmp = ($a['estimasi_waktu']['detik'] ?? PHP_FLOAT_MAX) <=> ($b['estimasi_waktu']['detik'] ?? PHP_FLOAT_MAX);
                    if ($durCmp !== 0) {
                        return $durCmp;
                    }
                }
                // Jika sama atau keduanya rute gagal, gunakan jarak lurus
                return ($a['jarak_lurus']['meter'] <=> $b['jarak_lurus']['meter']);
            }

            if ($sortBy === 'jarak_lurus') {
                return ($a['jarak_lurus']['meter'] <=> $b['jarak_lurus']['meter']);
            }

            // Default 'jarak_jalan':
            if ($a['rute_tersedia'] && $b['rute_tersedia']) {
                $distCmp = ($a['jarak_jalan']['meter'] ?? PHP_FLOAT_MAX) <=> ($b['jarak_jalan']['meter'] ?? PHP_FLOAT_MAX);
                if ($distCmp !== 0) {
                    return $distCmp;
                }
            }
            // Jika sama atau keduanya rute gagal, gunakan jarak lurus
            return ($a['jarak_lurus']['meter'] <=> $b['jarak_lurus']['meter']);
        })->values();

        // 5. Potong jika parameter limit lama diberikan (untuk backward compatibility)
        if ($limit !== null && $limit > 0) {
            $hasilFinal = $hasilSorted->take($limit)->values();
        } else {
            $hasilFinal = $hasilSorted;
        }

        // 6. Ambil geometri garis rute hanya untuk TOP_ROUTE_GEOMETRY faskes teratas yang rutenya tersedia
        $routeGeomCount = 0;
        $itemsWithGeometry = $hasilFinal->map(function ($item, int $index) use ($eventLat, $eventLng, &$routeGeomCount) {
            $item['rekomendasi_rank'] = $index + 1;

            if ($item['rute_tersedia'] && $routeGeomCount < self::TOP_ROUTE_GEOMETRY) {
                $routeResult = $this->osrmService->getRoute(
                    $eventLat,
                    $eventLng,
                    (float) $item['detail_faskes']['latitude'],
                    (float) $item['detail_faskes']['longitude']
                );

                if ($routeResult['success'] && ! empty($routeResult['geometry'])) {
                    $item['titik_rute_jalan'] = $routeResult['geometry'];
                    $item['geometri_tersedia'] = true;
                    // Sinkronkan jarak/durasi jika ada perbedaan kecil antara table dan route
                    if (isset($routeResult['distance_meters'])) {
                        $item['jarak_jalan']['meter'] = $routeResult['distance_meters'];
                        $item['jarak_jalan']['km'] = $routeResult['distance_km'];
                    }
                    if (isset($routeResult['duration_seconds'])) {
                        $item['estimasi_waktu']['detik'] = $routeResult['duration_seconds'];
                        $item['estimasi_waktu']['menit'] = $routeResult['duration_minutes'];
                    }
                    $routeGeomCount++;
                }
            }

            return $item;
        });

        // 7. Kembalikan response JSON terstruktur
        return response()->json([
            'success'                   => true,
            'titik_event'               => [
                'latitude'  => $eventLat,
                'longitude' => $eventLng,
            ],
            'parameter'                 => [
                'radius_km'       => $radiusKm,
                'jenis_faskes'    => $validated['jenis_faskes'] ?? 'semua',
                'sort_by'         => $sortBy,
                'limit'           => $limit,
                'prioritas_jenis' => $prioritasJenis,
            ],
            'total_dalam_radius'        => $totalDalamRadius,
            'total_ditampilkan'         => $itemsWithGeometry->count(),
            'dibatasi'                  => $dibatasi,
            'max_results'               => self::MAX_RESULTS,
            'prioritas_jenis'           => $prioritasJenis,
            'total_kandidat_dievaluasi' => $kandidatFaskes->count(),
            'total_faskes'              => $itemsWithGeometry->count(),
            'data'                      => $itemsWithGeometry,
        ]);
    }
}