<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeocodeController extends Controller
{
    /**
     * Bounding box kasar Kabupaten Banyumas: "left,top,right,bottom"
     * (lon_min, lat_max, lon_max, lat_min). Dipakai untuk MEMBIAS hasil
     * pencarian (bukan membatasi mutlak) agar alamat di sekitar Banyumas
     * diprioritaskan tanpa menutup kemungkinan hasil di luar area itu.
     */
    protected const BANYUMAS_VIEWBOX = '108.95,-7.15,109.50,-7.75';

    /**
     * Berapa lama (detik) hasil pencarian disimpan di cache.
     * Alamat jarang berubah koordinatnya, jadi cache boleh cukup lama —
     * ini juga yang paling penting untuk mematuhi kebijakan rate-limit
     * Nominatim (maksimal ~1 request/detik per aplikasi).
     */
    protected const CACHE_TTL_SECONDS = 86400; // 24 jam

    /**
     * Proxy pencarian alamat ke Nominatim (OpenStreetMap).
     *
     * CATATAN KEAMANAN & KEPATUHAN:
     * - Request tidak dikirim langsung dari browser ke Nominatim karena:
     *   (a) browser tidak bisa set header User-Agent kustom (dibutuhkan Nominatim),
     *   (b) supaya bisa di-cache & di-throttle di sisi server (route ini
     *       sebaiknya diberi middleware `throttle:20,1` di routes/api.php).
     * - Input `q` divalidasi panjangnya untuk mencegah query super panjang / abuse.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:3|max:200',
        ]);

        $query = trim($validated['q']);
        $cacheKey = 'geocode:search:' . md5(mb_strtolower($query));

        try {
            $results = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($query) {
                return $this->fetchFromNominatim($query);
            });

            return response()->json([
                'success' => true,
                'query'   => $query,
                'data'    => $results,
            ]);
        } catch (Throwable $e) {
            Log::error('Geocode search error: ' . $e->getMessage(), ['query' => $query]);

            return response()->json([
                'success' => false,
                'message' => 'Layanan pencarian alamat sedang tidak dapat diakses. Silakan tandai lokasi secara manual di peta.',
                'data'    => [],
            ], 200); // 200 sengaja: kegagalan geocoding bukan error fatal untuk form, biarkan frontend fallback ke manual
        }
    }

    /**
     * Ambil hasil pencarian dari Nominatim dan normalisasi ke bentuk ringkas.
     *
     * @return array<int, array{display_name: string, lat: float, lon: float, type: ?string, importance: ?float}>
     */
    protected function fetchFromNominatim(string $query): array
    {
        $userAgent = config('services.nominatim.user_agent');

        $response = Http::withHeaders([
            // WAJIB diisi sesuai kebijakan penggunaan Nominatim: nama aplikasi + kontak yang bisa dihubungi.
            // Isi lewat .env: NOMINATIM_USER_AGENT="SIG-Faskes-Banyumas (kontak@dinkes-banyumas.go.id)"
            'User-Agent' => $userAgent,
        ])
            ->timeout(6)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q'              => $query,
                'format'         => 'jsonv2',
                'addressdetails' => 1,
                'limit'          => 6,
                'countrycodes'   => 'id',
                'viewbox'        => self::BANYUMAS_VIEWBOX,
                'bounded'        => 0, // 0 = bias saja, bukan pembatas mutlak (alamat di luar Banyumas tetap bisa ketemu kalau memang diketik lengkap)
            ]);

        if (! $response->successful()) {
            Log::warning('Nominatim API non-2xx response', [
                'status' => $response->status(),
                'query'  => $query,
            ]);

            return [];
        }

        $raw = $response->json();

        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->map(function (array $item) {
                $lat = isset($item['lat']) ? (float) $item['lat'] : null;
                $lon = isset($item['lon']) ? (float) $item['lon'] : null;

                if ($lat === null || $lon === null || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                    return null;
                }

                return [
                    'display_name' => $item['display_name'] ?? $query,
                    'lat'          => $lat,
                    'lon'          => $lon,
                    'type'         => $item['type'] ?? null,
                    'importance'   => isset($item['importance']) ? (float) $item['importance'] : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}