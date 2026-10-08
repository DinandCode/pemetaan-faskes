<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaskesController extends Controller
{
    /**
     * Menampilkan daftar semua faskes dengan opsi filter dinamis.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->buildFilterQuery($request);

        $faskes = $request->boolean('all', false)
            ? $query->get()
            : $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'total'   => is_countable($faskes) ? count($faskes) : $faskes->total(),
            'data'    => $faskes,
        ]);
    }

    /**
     * Endpoint API khusus filter atribut dinamis untuk marker peta real-time.
     */
    public function filter(Request $request): JsonResponse
    {
        $query = $this->buildFilterQuery($request);

        // Hanya ambil faskes yang memiliki koordinat lengkap
        $faskes = $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('nama', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'total'   => $faskes->count(),
            'data'    => $faskes,
        ]);
    }

    /**
     * Endpoint API ringan untuk render ribuan marker peta (Tugas 8A).
     * Menggunakan query builder murni (toBase) tanpa eager loading & tanpa $appends Eloquent,
     * sehingga sangat hemat memori dan cepat dieksekusi.
     *
     * Format respons data: Array of compact objects:
     * [{ id, nama, jenis_faskes, lat, lng, status, kecamatan }]
     */
    public function markers(Request $request): JsonResponse
    {
        $query = $this->buildFilterQuery($request);

        // Buang eager loading model relasi agar query ringan
        $query->setEagerLoads([]);

        // Konversi ke Query Builder murni (stdClass, bukan model Eloquent ber-appends)
        $rawQuery = $query->toBase()
            ->select([
                'id',
                'nama',
                'jenis_faskes',
                'latitude',
                'longitude',
                'status',
                'kecamatan',
            ]);

        // Pagar validasi koordinat: hanya koordinat valid (not null & rentang geografis benar)
        $rawQuery->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [-90, 90])
            ->whereBetween('longitude', [-180, 180]);

        // Opsional: Filter bounding box jika dikirim klien (minLng,minLat,maxLng,maxLat)
        if ($request->filled('bbox')) {
            $parts = explode(',', $request->query('bbox'));
            if (count($parts) === 4) {
                $minLng = (float) $parts[0];
                $minLat = (float) $parts[1];
                $maxLng = (float) $parts[2];
                $maxLat = (float) $parts[3];
                if ($minLat <= $maxLat && $minLng <= $maxLng) {
                    $rawQuery->whereBetween('latitude', [$minLat, $maxLat])
                        ->whereBetween('longitude', [$minLng, $maxLng]);
                }
            }
        }

        // Batasi jumlah titik (default 20000)
        $limit = min(20000, max(1, $request->integer('limit', 20000)));
        $rows = $rawQuery->orderBy('nama', 'asc')->limit($limit)->get();

        $data = $rows->map(function ($row) {
            return [
                'id'           => (int) $row->id,
                'nama'         => $row->nama,
                'jenis_faskes' => $row->jenis_faskes,
                'lat'          => (float) $row->latitude,
                'lng'          => (float) $row->longitude,
                'status'       => $row->status,
                'kecamatan'    => $row->kecamatan,
            ];
        });

        return response()->json([
            'success' => true,
            'total'   => $data->count(),
            'data'    => $data,
        ])->header('Cache-Control', 'public, s-maxage=30, stale-while-revalidate=60');
    }

    /**
     * Endpoint API ringan untuk menghitung jumlah faskes per jenis_faskes berdasarkan filter aktif (Tugas 9).
     * Menggunakan GROUP BY pada Query Builder murni tanpa memuat koordinat atau instansiasi model.
     *
     * Format respons:
     * {
     *   success: true,
     *   total: 852,
     *   counts: {
     *     rumah_sakit: 12,
     *     puskesmas: 40,
     *     ...
     *   }
     * }
     */
    public function markersCount(Request $request): JsonResponse
    {
        $query = $this->buildFilterQuery($request);

        // Buang eager loading relasi
        $query->setEagerLoads([]);

        $rawQuery = $query->toBase();

        // Pagar validasi koordinat: hanya hitung titik yang koordinatnya valid
        $rawQuery->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [-90, 90])
            ->whereBetween('longitude', [-180, 180]);

        $rows = $rawQuery->select([
            'jenis_faskes',
            DB::raw('COUNT(*) as aggregate_count')
        ])
        ->groupBy('jenis_faskes')
        ->get();

        $counts = [
            'rumah_sakit'    => 0,
            'puskesmas'      => 0,
            'klinik_utama'   => 0,
            'klinik_pratama' => 0,
            'laboratorium'   => 0,
            'griya_sehat'    => 0,
            'upkdk'          => 0,
            'tpmd'           => 0,
            'tpmdg'          => 0,
            'tpmb'           => 0,
            'tpmp'           => 0,
        ];

        $total = 0;
        foreach ($rows as $row) {
            $cnt = (int) $row->aggregate_count;
            $counts[$row->jenis_faskes] = $cnt;
            $total += $cnt;
        }

        return response()->json([
            'success' => true,
            'total'   => $total,
            'counts'  => $counts,
        ])->header('Cache-Control', 'public, s-maxage=30, stale-while-revalidate=60');
    }

    /**
     * Membangun Query Eloquent dengan filter relasi whereHas dinamis.
     */
    protected function buildFilterQuery(Request $request): Builder
    {
        $query = Faskes::query()->with([
            'puskesmasDetail',
            'rumahSakitDetail',
            'klinikPratamaDetail',
            'klinikUtamaDetail',
            'laboratoriumDetail',
            'upkdkDetail',
            'griyaSehatDetail',
            'fieldValues.definition',
        ]);

        // 1. Filter Jenis Faskes (bisa array, string tunggal, atau comma-separated)
        if ($request->has('jenis_faskes')) {
            $jenis = $request->input('jenis_faskes');
            if (is_string($jenis)) {
                $jenis = explode(',', $jenis);
            }
            $jenis = array_filter((array) $jenis);
            if (! empty($jenis) && ! in_array('semua', $jenis, true)) {
                $query->whereIn('jenis_faskes', $jenis);
            }
        }

        // 2. Filter Spesifik: Memiliki Ambulans Gadar / Transport (whereHas pada child tables)
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

        // 3. Filter Spesifik: Melayani BPJS (whereHas pada Klinik Pratama, Klinik Utama & Faskes Pemerintah)
        if ($request->boolean('has_bpjs')) {
            $query->where(function ($q) {
                $q->whereHas('klinikPratamaDetail', function ($sub) {
                    $sub->where('bpjs', true);
                })->orWhereHas('klinikUtamaDetail', function ($sub) {
                    $sub->where('bpjs', true);
                })->orWhereIn('jenis_faskes', ['puskesmas', 'rumah_sakit']);
            });
        }

        // 4. Filter Spesifik: Memiliki Bed Rawat Inap (whereHas pada Puskesmas/Klinik & RS)
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

        // 5. Filter Spesifik: Status PONED (Khusus Puskesmas via whereHas)
        if ($request->boolean('has_poned')) {
            $query->whereHas('puskesmasDetail', function ($sub) {
                $sub->where('poned', 'Ya PONED');
            });
        }

        // 6. Filter Tambahan: Kecamatan, Status, Search
        if ($request->filled('kecamatan')) {
            $query->where('kecamatan', 'ilike', '%' . $request->kecamatan . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default aktif untuk peta jika tidak dispesifikasikan
            if ($request->boolean('only_active', true)) {
                $query->where('status', 'aktif');
            }
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', $search)
                  ->orWhere('alamat', 'ilike', $search)
                  ->orWhere('desa', 'ilike', $search)
                  ->orWhere('kecamatan', 'ilike', $search);
            });
        }

        // 7. Filter Atribut Lanjutan (Tugas 4: Jumlah, Kategori, Izin Operasional, Custom Fields)
        \App\Support\FaskesAttributeFilter::apply($query, $request);

        return $query;
    }

    /**
     * Menampilkan data spesifik faskes beserta detailnya.
     */
    public function show(int $id): JsonResponse
    {
        $faskes = Faskes::with([
            'puskesmasDetail',
            'rumahSakitDetail',
            'klinikPratamaDetail',
            'klinikUtamaDetail',
            'laboratoriumDetail',
            'upkdkDetail',
            'griyaSehatDetail',
            'fieldValues.definition',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'faskes' => $faskes,
                'detail' => $faskes->detail,
            ],
        ]);
    }

    /**
     * Pencarian faskes terdekat berbasis query spasial PostGIS ST_DistanceSphere.
     */
    public function terdekat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'radius_km'    => 'nullable|numeric|min:0.1',
            'jenis_faskes' => 'nullable',
            'limit'        => 'nullable|integer|min:1|max:50',
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $radiusKm = isset($validated['radius_km']) ? (float) $validated['radius_km'] : 5.0;
        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 10;

        $query = $this->buildFilterQuery($request)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withDistance($latitude, $longitude)
            ->withinRadius($latitude, $longitude, $radiusKm);

        $faskesList = $query->orderBy('jarak_lurus_meter', 'asc')
            ->limit($limit)
            ->get();

        $data = $faskesList->map(function ($faskes) {
            $meter = round((float) $faskes->jarak_lurus_meter, 2);
            return [
                'id'                => $faskes->id,
                'nama'              => $faskes->nama,
                'jenis_faskes'      => $faskes->jenis_faskes,
                'alamat'            => $faskes->alamat,
                'kecamatan'         => $faskes->kecamatan,
                'desa'              => $faskes->desa,
                'latitude'          => (float) $faskes->latitude,
                'longitude'         => (float) $faskes->longitude,
                'nomor_telepon'     => $faskes->nomor_telepon,
                'status'            => $faskes->status,
                'jarak_lurus_meter' => $meter,
                'jarak_lurus_km'    => round($meter / 1000, 2),
                'detail'            => $faskes->detail,
            ];
        });

        return response()->json([
            'success'         => true,
            'titik_pusat'     => [
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ],
            'radius_km'       => $radiusKm,
            'total_ditemukan' => $data->count(),
            'data'            => $data,
        ]);
    }

    /**
     * Menyimpan data faskes baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama'          => 'required|string|max:255',
            'jenis_faskes'  => 'required|string|in:puskesmas,rumah_sakit,klinik_pratama,klinik_utama,laboratorium,upkdk,griya_sehat,tpmd,tpmdg,tpmb,tpmp',
            'alamat'        => 'nullable|string',
            'kecamatan'     => 'nullable|string|max:255',
            'desa'          => 'nullable|string|max:255',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
            'nomor_telepon' => 'nullable|string|max:255',
            'status'                    => 'nullable|string|in:aktif,nonaktif',
            'detail'                    => 'nullable|array',
            'detail.ambulans_transport' => 'nullable|integer|min:0',
            'detail.ambulans_gadar'     => 'nullable|integer|min:0',
            'detail.kepemilikan'        => 'nullable|string|in:Swasta,Pemerintah',
        ]);

        $detailData = $validated['detail'] ?? [];
        unset($validated['detail']);

        DB::beginTransaction();
        try {
            $faskes = Faskes::create($validated);

            if (! empty($detailData)) {
                $this->saveFaskesDetail($faskes, $detailData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Faskes berhasil ditambahkan.',
                'data'    => $faskes->fresh(['puskesmasDetail', 'rumahSakitDetail', 'klinikPratamaDetail', 'klinikUtamaDetail', 'laboratoriumDetail', 'upkdkDetail', 'griyaSehatDetail']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan faskes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menghapus faskes (relasi detail dihapus secara cascade).
     */
    public function destroy(int $id): JsonResponse
    {
        $faskes = Faskes::findOrFail($id);
        $faskes->delete();

        return response()->json([
            'success' => true,
            'message' => 'Faskes berhasil dihapus.',
        ]);
    }

    /**
     * Helper untuk menyimpan data detail relasi child sesuai jenis faskes.
     */
    protected function saveFaskesDetail(Faskes $faskes, array $detailData): void
    {
        switch ($faskes->jenis_faskes) {
            case 'puskesmas':
                $faskes->puskesmasDetail()->create($detailData);
                break;
            case 'rumah_sakit':
                $faskes->rumahSakitDetail()->create($detailData);
                break;
            case 'klinik_pratama':
                $faskes->klinikPratamaDetail()->create($detailData);
                break;
            case 'klinik_utama':
                $faskes->klinikUtamaDetail()->create($detailData);
                break;
            case 'laboratorium':
                $faskes->laboratoriumDetail()->create($detailData);
                break;
            case 'upkdk':
                $faskes->upkdkDetail()->create($detailData);
                break;
            case 'griya_sehat':
                $faskes->griyaSehatDetail()->create($detailData);
                break;
        }
    }
}
