<?php

namespace App\Services;

use App\Models\Faskes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OsrmService
{
    /**
     * URL dasar OSRM API.
     */
    protected string $baseUrl;

    /**
     * Timeout request HTTP dalam detik.
     */
    protected int $timeout;

    /**
     * Jarak maksimal (meter) yang ditoleransi antara koordinat asli dengan titik
     * hasil "snap" OSRM ke ruas jalan terdekat. Kalau OSRM menempelkan koordinat
     * ke jalan yang jauh (indikasi titik input tidak valid / di area tanpa jalan
     * terdata), hasil rute dianggap tidak dapat dipercaya dan ditolak.
     */
    protected int $maxSnapDistanceMeters;

    /**
     * Radius pencarian jalan (meter) yang dikirim ke parameter `radiuses` OSRM.
     * Membatasi seberapa jauh OSRM boleh mencari jalan terdekat dari titik asli.
     */
    protected int $snapSearchRadiusMeters;

    public function __construct(
        ?string $baseUrl = null,
        ?int $timeout = null,
        ?int $maxSnapDistanceMeters = null,
        ?int $snapSearchRadiusMeters = null
    ) {
        $this->baseUrl = rtrim(
            $baseUrl ?? config('services.osrm.base_url', 'https://router.project-osrm.org'),
            '/'
        );
        $this->timeout = $timeout ?? (int) config('services.osrm.timeout', 10);
        $this->maxSnapDistanceMeters = $maxSnapDistanceMeters
            ?? (int) config('services.osrm.max_snap_distance_meters', 500);
        $this->snapSearchRadiusMeters = $snapSearchRadiusMeters
            ?? (int) config('services.osrm.snap_search_radius_meters', 750);
    }

    /**
     * Mengambil rute jalan dan jarak tempuh antara dua koordinat.
     *
     * Format OSRM API membutuhkan koordinat: {lon1},{lat1};{lon2},{lat2}
     *
     * @param float $originLat Latitude titik awal (titik event/kejadian)
     * @param float $originLng Longitude titik awal (titik event/kejadian)
     * @param float $destLat   Latitude titik tujuan (titik faskes)
     * @param float $destLng   Longitude titik tujuan (titik faskes)
     * @return array{
     *     success: bool,
     *     distance_km: float,
     *     duration_minutes: float,
     *     distance_meters: float,
     *     duration_seconds: float,
     *     geometry: ?array,
     *     snap_distance_origin_meters: ?float,
     *     snap_distance_dest_meters: ?float,
     *     message: ?string
     * }
     */
    public function getRoute(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        // Guard: validasi rentang koordinat sebelum membentuk URL sama sekali.
        // Mencegah request tak berguna ke OSRM dan mencegah nilai aneh
        // ikut terselip ke path URL.
        if (! $this->isValidLatLng($originLat, $originLng) || ! $this->isValidLatLng($destLat, $destLng)) {
            return $this->errorResponse('Koordinat asal atau tujuan tidak valid.');
        }

        // OSRM menggunakan urutan: longitude,latitude
        $coordinates = "{$originLng},{$originLat};{$destLng},{$destLat}";
        $url = "{$this->baseUrl}/route/v1/driving/{$coordinates}";

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url, [
                    'overview'   => 'full',
                    'geometries' => 'geojson',
                    'steps'      => 'false',
                    // Batasi radius pencarian jalan terdekat dari masing-masing titik.
                    // Kalau tidak ada jalan dalam radius ini, OSRM akan langsung
                    // mengembalikan kode error (mis. NoSegment) alih-alih diam-diam
                    // menempel ke jalan yang jauh sekali.
                    'radiuses'   => "{$this->snapSearchRadiusMeters};{$this->snapSearchRadiusMeters}",
                ]);

            if (! $response->successful()) {
                Log::warning('OSRM API Error Response', [
                    'status' => $response->status(),
                    'url'    => $url,
                ]);

                return $this->errorResponse('Gagal menghubungi layanan OSRM API (HTTP ' . $response->status() . ').');
            }

            $data = $response->json();

            if (! isset($data['code']) || $data['code'] !== 'Ok' || empty($data['routes'])) {
                $code = $data['code'] ?? 'Unknown';

                return $this->errorResponse("Rute jalan tidak ditemukan oleh OSRM (Status: {$code}).");
            }

            $route = $data['routes'][0];
            $waypoints = $data['waypoints'] ?? [];

            // Validasi jarak "snap" ke jalan terdekat untuk kedua titik.
            // Kalau salah satu titik sebenarnya jauh dari jaringan jalan yang terdata,
            // hasil rute tidak boleh dipakai sebagai dasar keputusan darurat.
            $snapOrigin = isset($waypoints[0]['distance']) ? (float) $waypoints[0]['distance'] : null;
            $snapDest = isset($waypoints[1]['distance']) ? (float) $waypoints[1]['distance'] : null;

            if (($snapOrigin !== null && $snapOrigin > $this->maxSnapDistanceMeters) ||
                ($snapDest !== null && $snapDest > $this->maxSnapDistanceMeters)) {
                Log::warning('OSRM snap distance melebihi batas toleransi', [
                    'snap_origin_meters' => $snapOrigin,
                    'snap_dest_meters'   => $snapDest,
                    'max_allowed_meters' => $this->maxSnapDistanceMeters,
                    'coordinates'        => $coordinates,
                ]);

                return $this->errorResponse(
                    'Koordinat terlalu jauh dari jalan yang terdata (snap distance melebihi batas aman).'
                );
            }

            $distanceMeters = (float) ($route['distance'] ?? 0);
            $durationSeconds = (float) ($route['duration'] ?? 0);

            $distanceKm = round($distanceMeters / 1000, 2);
            $durationMinutes = round($durationSeconds / 60, 1);
            $geometry = $route['geometry'] ?? null;

            return [
                'success'                     => true,
                'distance_km'                 => $distanceKm,
                'duration_minutes'            => $durationMinutes,
                'distance_meters'             => $distanceMeters,
                'duration_seconds'            => $durationSeconds,
                'geometry'                    => $geometry,
                'snap_distance_origin_meters' => $snapOrigin,
                'snap_distance_dest_meters'   => $snapDest,
                'message'                     => null,
            ];
        } catch (Throwable $e) {
            Log::error('OSRM Exception: ' . $e->getMessage(), [
                'url' => $url,
            ]);

            return $this->errorResponse('Terjadi kesalahan saat memproses rute ke layanan OSRM.');
        }
    }

    /**
     * Helper untuk mengambil rute dari koordinat event langsung ke objek Model Faskes.
     */
    public function getRouteToFaskes(float $originLat, float $originLng, Faskes $faskes): array
    {
        if (is_null($faskes->latitude) || is_null($faskes->longitude)) {
            return $this->errorResponse("Faskes [{$faskes->nama}] belum memiliki data koordinat latitude/longitude.");
        }

        $result = $this->getRoute($originLat, $originLng, (float) $faskes->latitude, (float) $faskes->longitude);
        $result['faskes'] = [
            'id'           => $faskes->id,
            'nama'         => $faskes->nama,
            'jenis_faskes' => $faskes->jenis_faskes,
            'alamat'       => $faskes->alamat,
        ];

        return $result;
    }

    /**
     * Validasi sederhana rentang latitude/longitude.
     */
    protected function isValidLatLng(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * Template struktur response saat terjadi kesalahan.
     * `duration_seconds`/`duration_minutes` sengaja TIDAK diisi 0.0 di controller pemanggil —
     * controller yang menentukan nilai null agar tidak pernah keliru dianggap "tercepat" saat sorting.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success'                     => false,
            'distance_km'                 => 0.0,
            'duration_minutes'            => 0.0,
            'distance_meters'             => 0.0,
            'duration_seconds'            => 0.0,
            'geometry'                    => null,
            'snap_distance_origin_meters' => null,
            'snap_distance_dest_meters'   => null,
            'message'                     => $message,
        ];
    }
}