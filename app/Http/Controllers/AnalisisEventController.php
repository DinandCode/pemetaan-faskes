<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use App\Services\OsrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalisisEventController extends Controller
{
    /**
     * Batas atas jumlah kandidat faskes yang boleh dikirim ke OSRM dalam satu request.
     * Mencegah penyalahgunaan (radius/limit besar -> ratusan panggilan HTTP ke OSRM sekaligus).
     */
    protected const MAX_CANDIDATE_POOL = 30;

    /**
     * Faktor pengali kandidat sebelum di-routing, relatif terhadap $limit yang diminta.
     * Kandidat lebih banyak dari $limit final diperlukan karena urutan jarak LURUS
     * belum tentu sama dengan urutan jarak JALAN — faskes yang sedikit lebih jauh secara
     * garis lurus bisa jadi lebih dekat lewat jalan.
     */
    protected const CANDIDATE_MULTIPLIER = 3;

    /**
     * Service OSRM untuk kalkulasi rute jalan nyata.
     */
    protected OsrmService $osrmService;

    public function __construct(OsrmService $osrmService)
    {
        $this->osrmService = $osrmService;
    }

    /**
     * Menganalisis faskes terdekat dari koordinat event berdasarkan radius (PostGIS ST_DistanceSphere),
     * menghitung jarak rute jalan & estimasi waktu tempuh (OSRM), dan mengembalikan data GeoJSON rute.
     */
    public function analisis(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            // Batas atas radius dipasang agar tidak ada yang query radius ekstrem (mis. 9999 km)
            // yang bisa menyapu seluruh tabel faskes dan membebani server + OSRM.
            'radius_km'      => 'nullable|numeric|min:0.1|max:100',
            // FIX: jenis_faskes dikirim frontend sebagai array (jenis_faskes[]=a&jenis_faskes[]=b),
            // rule sebelumnya (`string|in:...`) tidak cocok dengan bentuk array dan bisa membuat
            // filter jenis gagal tervalidasi secara diam-diam.
            'jenis_faskes'   => 'nullable|array',
            'jenis_faskes.*' => 'string|in:puskesmas,rumah_sakit,klinik_pratama,klinik_utama,laboratorium,upkdk,griya_sehat,tpmd,tpmdg,tpmb,tpmp',
            'limit'          => 'nullable|integer|min:1|max:20',
            'sort_by'        => 'nullable|string|in:jarak_jalan,jarak_lurus,estimasi_waktu',
            'has_ambulans'   => 'nullable|boolean',
            'has_bpjs'       => 'nullable|boolean',
            'has_rawat_inap' => 'nullable|boolean',
            'has_poned'      => 'nullable|boolean',
        ]);

        $eventLat = (float) $validated['latitude'];
        $eventLng = (float) $validated['longitude'];
        $radiusKm = isset($validated['radius_km']) ? (float) $validated['radius_km'] : 5.0;
        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 5;
        $sortBy = $validated['sort_by'] ?? 'jarak_jalan';

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

        // FIX AKURASI: ambil kandidat lebih banyak dari $limit final SEBELUM dihitung jarak jalannya,
        // supaya faskes yang sedikit lebih jauh secara garis lurus tapi ternyata lebih dekat lewat
        // jalan tetap ikut dipertimbangkan. Dibatasi MAX_CANDIDATE_POOL agar jumlah panggilan OSRM
        // per request tetap terkendali.
        $candidatePool = min(
            max($limit * self::CANDIDATE_MULTIPLIER, 15),
            self::MAX_CANDIDATE_POOL
        );

        $kandidatFaskes = $query->orderBy('jarak_lurus_meter', 'asc')
            ->limit($candidatePool)
            ->get();

        // 2. Hitung jarak nyata jalan raya & estimasi waktu menggunakan OSRM API
        $hasilAnalisis = $kandidatFaskes->map(function (Faskes $faskes) use ($eventLat, $eventLng) {
            $jarakLurusMeter = round((float) $faskes->jarak_lurus_meter, 2);
            $jarakLurusKm = round($jarakLurusMeter / 1000, 2);

            $osrmResult = $this->osrmService->getRoute(
                $eventLat,
                $eventLng,
                (float) $faskes->latitude,
                (float) $faskes->longitude
            );

            $ruteTersedia = (bool) $osrmResult['success'];

            // FIX KRITIS: saat OSRM gagal, jangan isi jarak/estimasi dengan 0.0 atau fallback
            // jarak lurus yang dicampur dengan data valid lainnya — keduanya bisa membuat
            // faskes dengan rute GAGAL malah tampak sebagai yang "tercepat" saat sorting.
            // Nilai null menandakan data tidak tersedia secara eksplisit.
            $jarakJalanMeter = $ruteTersedia ? $osrmResult['distance_meters'] : null;
            $jarakJalanKm = $ruteTersedia ? $osrmResult['distance_km'] : null;
            $estimasiDetik = $ruteTersedia ? $osrmResult['duration_seconds'] : null;
            $estimasiMenit = $ruteTersedia ? $osrmResult['duration_minutes'] : null;
            $titikRuteJalan = $osrmResult['geometry'] ?? null;

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
                'jarak_lurus' => [
                    'meter' => $jarakLurusMeter,
                    'km'    => $jarakLurusKm,
                ],
                'jarak_jalan' => [
                    'meter' => $jarakJalanMeter,
                    'km'    => $jarakJalanKm,
                ],
                'estimasi_waktu' => [
                    'detik' => $estimasiDetik,
                    'menit' => $estimasiMenit,
                ],
                'titik_rute_jalan' => $titikRuteJalan,
                'rute_tersedia'    => $ruteTersedia,
                'pesan_rute'       => $ruteTersedia ? null : $osrmResult['message'],
            ];
        });

        // 3. Sorting hasil akhir sesuai preferensi (jarak_jalan / estimasi_waktu / jarak_lurus).
        // FIX KRITIS: item dengan rute_tersedia === false SELALU didorong ke akhir daftar,
        // apa pun sort_by yang dipilih, karena datanya tidak valid dan tidak boleh dianggap
        // sebagai kandidat tercepat/terdekat.
       $hasilSorted = match ($sortBy) {
    'jarak_jalan' => $hasilAnalisis->sortBy([
        fn ($a, $b) => ($a['rute_tersedia'] ? 0 : 1) <=> ($b['rute_tersedia'] ? 0 : 1),
        fn ($a, $b) => ($a['jarak_jalan']['meter'] ?? PHP_FLOAT_MAX)
                        <=> ($b['jarak_jalan']['meter'] ?? PHP_FLOAT_MAX),
    ])->values(),

    'estimasi_waktu' => $hasilAnalisis->sortBy([
        fn ($a, $b) => ($a['rute_tersedia'] ? 0 : 1) <=> ($b['rute_tersedia'] ? 0 : 1),
        fn ($a, $b) => ($a['estimasi_waktu']['detik'] ?? PHP_FLOAT_MAX)
                        <=> ($b['estimasi_waktu']['detik'] ?? PHP_FLOAT_MAX),
    ])->values(),

    'jarak_lurus' => $hasilAnalisis->sortBy('jarak_lurus.meter')->values(),

    default => $hasilAnalisis->values(),
};

        // 4. Potong ke jumlah $limit final SETELAH kandidat lebih besar selesai di-sort & di-routing.
        $hasilFinal = $hasilSorted->take($limit)->values();

        // 5. Kembalikan response JSON terstruktur
        return response()->json([
            'success'     => true,
            'titik_event' => [
                'latitude'  => $eventLat,
                'longitude' => $eventLng,
            ],
            'parameter' => [
                'radius_km'    => $radiusKm,
                'jenis_faskes' => $validated['jenis_faskes'] ?? 'semua',
                'sort_by'      => $sortBy,
                'limit'        => $limit,
            ],
            'total_kandidat_dievaluasi' => $kandidatFaskes->count(),
            'total_faskes'              => $hasilFinal->count(),
            'data'                      => $hasilFinal,
        ]);
    }
}