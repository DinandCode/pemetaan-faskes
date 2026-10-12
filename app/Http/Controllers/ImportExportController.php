<?php

namespace App\Http\Controllers;

use App\Exports\AnalisisEventExport;
use App\Exports\FaskesPerKecamatanExport;
use App\Imports\FaskesImport;
use App\Models\Faskes;
use App\Services\FaskesImportService;
use App\Services\OsrmService;
use App\Support\FaskesExportQueryBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    /**
     * Batas baris maksimal untuk export PDF agar tidak melebihi payload limit serverless Vercel (4.5 MB).
     */
    public const MAX_PDF_ROWS = 2500;

    /**
     * Pastikan direktori temporer DomPDF dan Excel siap di /tmp atau storage.
     */
    protected function prepareTempDirectory(): void
    {
        $tempPath = sys_get_temp_dir();
        if ($tempPath && is_dir($tempPath)) {
            File::ensureDirectoryExists($tempPath . '/dompdf');
        }

        $storageCache = storage_path('framework/cache');
        File::ensureDirectoryExists($storageCache);
    }

    /**
     * Unduh template file Excel import yang disesuaikan dengan jenis faskes.
     */
    public function downloadTemplate(Request $request)
    {
        $jenisFaskes = $request->query('jenis_faskes', 'puskesmas');
        $supported = FaskesImportService::supportedJenis();

        if (! array_key_exists($jenisFaskes, $supported)) {
            return redirect()->back()->with('error', 'Jenis faskes tidak valid.');
        }

        return FaskesImportService::generateTemplate($jenisFaskes);
    }

    /**
     * Validasi/Cek file Excel import tanpa menyimpan ke database (dry-run).
     */
    public function checkImport(Request $request, FaskesImportService $importService)
    {
        $supported = array_keys(FaskesImportService::supportedJenis());

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'file'         => 'required|file|mimes:xlsx,xls,csv|max:4096',
            'jenis_faskes' => 'required|string|in:' . implode(',', $supported),
        ], [
            'file.required'   => 'File Excel wajib diunggah.',
            'file.mimes'      => 'File harus berformat .xlsx, .xls, atau .csv.',
            'file.max'        => 'Ukuran file maksimal 4 MB.',
            'jenis_faskes.in' => 'Jenis faskes tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()->all(),
            ], 422);
        }

        $result = $importService->parseAndValidate(
            $request->file('file'),
            $request->input('jenis_faskes'),
            $request->only(['on_duplicate', 'transaction_mode'])
        );

        return response()->json($result);
    }

    /**
     * Import data Faskes dari file Excel Dinkes (Mendukung format per-jenis baru & format lama).
     */
    public function importFaskes(Request $request, FaskesImportService $importService)
    {
        // 1. Jika ada parameter jenis_faskes, gunakan alur Import Baru (Per Jenis Faskes)
        if ($request->filled('jenis_faskes')) {
            $supported = array_keys(FaskesImportService::supportedJenis());

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'file'             => 'required|file|mimes:xlsx,xls,csv|max:4096',
                'jenis_faskes'     => 'required|string|in:' . implode(',', $supported),
                'on_duplicate'     => 'nullable|string|in:skip,update',
                'transaction_mode' => 'nullable|string|in:valid_only,rollback_all',
            ], [
                'file.required'   => 'File Excel wajib diunggah.',
                'file.mimes'      => 'File harus berformat .xlsx, .xls, atau .csv.',
                'file.max'        => 'Ukuran file maksimal 4 MB.',
                'jenis_faskes.in' => 'Jenis faskes tidak valid.',
            ]);

            if ($validator->fails()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors()->first(),
                        'errors'  => $validator->errors()->all(),
                    ], 422);
                }
                return redirect()->route('faskes.index')->with('error', $validator->errors()->first());
            }

            $options = [
                'on_duplicate'     => $request->input('on_duplicate', 'skip'),
                'transaction_mode' => $request->input('transaction_mode', 'valid_only'),
            ];

            // Parse & validasi file
            $parsed = $importService->parseAndValidate(
                $request->file('file'),
                $request->input('jenis_faskes'),
                $options
            );

            if (! $parsed['success']) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json($parsed, 422);
                }
                return redirect()->route('faskes.index')->with('error', $parsed['message']);
            }

            // Jika mode rollback_all dan terdapat error pada file, batalkan proses
            if ($options['transaction_mode'] === 'rollback_all' && $parsed['error_count'] > 0) {
                $errRes = [
                    'success'         => false,
                    'message'         => 'Impor dibatalkan karena mode "Batalkan semua jika ada error" aktif dan ditemukan ' . $parsed['error_count'] . ' baris bermasalah.',
                    'total_rows'      => $parsed['total_rows'],
                    'valid_count'     => $parsed['valid_count'],
                    'error_count'     => $parsed['error_count'],
                    'duplicate_count' => $parsed['duplicate_count'],
                    'errors'          => $parsed['errors'],
                ];
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json($errRes, 422);
                }
                return redirect()->route('faskes.index')->with('error', $errRes['message']);
            }

            // Eksekusi penyimpanan ke database
            $execResult = $importService->executeImport(
                $parsed['rows_data'],
                $request->input('jenis_faskes'),
                $options
            );

            $execResult['total_rows']      = $parsed['total_rows'];
            $execResult['valid_count']     = $parsed['valid_count'];
            $execResult['error_count']     = $parsed['error_count'];
            $execResult['duplicate_count'] = $parsed['duplicate_count'];
            $execResult['errors']          = $parsed['errors'];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($execResult);
            }

            $flashType = $execResult['success'] ? 'success' : 'error';
            return redirect()->route('faskes.index')->with($flashType, $execResult['message']);
        }

        // 2. Alur Lama (Fallback / Format Rekapitulasi Multijenis Tanpa Jenis Terpilih)
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new FaskesImport();
            Excel::import($import, $request->file('file'));

            $message = "Berhasil mengimpor {$import->importedCount} data fasilitas kesehatan.";
            if (count($import->errors) > 0) {
                $message .= ' Peringatan (' . count($import->errors) . ' baris): ' . implode('; ', array_slice($import->errors, 0, 3));
            }

            return redirect()->route('faskes.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('FaskesImport Legacy Error: ' . $e->getMessage());
            return redirect()->route('faskes.index')->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    /**
     * Export rekapitulasi seluruh Faskes ke Excel dengan filter aktif konsisten.
     */
    public function exportFaskesExcel(Request $request)
    {
        $this->prepareTempDirectory();

        $query = FaskesExportQueryBuilder::build($request);
        $timestamp = Carbon::now('Asia/Jakarta')->format('Ymd_His');
        $filename = 'rekap_faskes_banyumas_' . $timestamp . '.xlsx';

        return Excel::download(new FaskesPerKecamatanExport($query), $filename);
    }

    /**
     * Export rekapitulasi seluruh Faskes ke PDF dengan filter aktif konsisten & proteksi batas baris.
     */
    public function exportFaskesPdf(Request $request)
    {
        $this->prepareTempDirectory();

        $query = FaskesExportQueryBuilder::build($request);
        $count = $query->count();

        if ($count > self::MAX_PDF_ROWS) {
            return redirect()->back()->with('error', "Terlalu banyak data untuk di-export ke satu file PDF ({$count} data, batas maksimal " . self::MAX_PDF_ROWS . ' baris). Silakan persempit filter jenis atau kecamatan terlebih dahulu, atau gunakan export Excel.');
        }

        $faskesList = $query->orderBy('kecamatan')->orderBy('jenis_faskes')->orderBy('nama')->get();
        $printedAt = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i');

        $pdf = Pdf::loadView('exports.faskes-rekap-pdf', compact('faskesList', 'printedAt'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
            ]);

        $timestamp = Carbon::now('Asia/Jakarta')->format('Ymd_His');
        return $pdf->download('rekap_faskes_banyumas_' . $timestamp . '.pdf');
    }

    /**
     * Export hasil analisis event ke Excel (mendukung GET dan POST).
     */
    public function exportAnalisisExcel(Request $request)
    {
        $this->prepareTempDirectory();

        $request->validate([
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'radius_km'    => 'nullable|numeric|min:0.1',
            'jenis_faskes' => 'nullable',
            'limit'        => 'nullable|integer|min:1|max:50',
        ]);

        $data = $this->runAnalisis($request);

        $parameter = [
            'latitude'     => $request->input('latitude'),
            'longitude'    => $request->input('longitude'),
            'radius_km'    => $request->input('radius_km', 5),
            'jenis_faskes' => $request->input('jenis_faskes', 'semua'),
        ];

        $timestamp = Carbon::now('Asia/Jakarta')->format('Ymd_His');
        $filename = 'analisis_event_' . $timestamp . '.xlsx';
        return Excel::download(new AnalisisEventExport($data, $parameter), $filename);
    }

    /**
     * Export hasil analisis event ke PDF (mendukung GET dan POST).
     */
    public function exportAnalisisPdf(Request $request)
    {
        $this->prepareTempDirectory();

        $request->validate([
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'radius_km'    => 'nullable|numeric|min:0.1',
            'jenis_faskes' => 'nullable',
            'limit'        => 'nullable|integer|min:1|max:50',
        ]);

        $data = $this->runAnalisis($request);

        $parameter = [
            'latitude'     => $request->input('latitude'),
            'longitude'    => $request->input('longitude'),
            'radius_km'    => $request->input('radius_km', 5),
            'jenis_faskes' => $request->input('jenis_faskes', 'semua'),
        ];

        $printedAt = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i');

        $pdf = Pdf::loadView('exports.analisis-event-pdf', compact('data', 'parameter', 'printedAt'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
            ]);

        $timestamp = Carbon::now('Asia/Jakarta')->format('Ymd_His');
        return $pdf->download('analisis_event_' . $timestamp . '.pdf');
    }

    /**
     * Menjalankan logika spasial & routing OSRM untuk analisis event,
     * identik dengan perilaku di AnalisisEventController.
     */
    protected function runAnalisis(Request $request): array
    {
        $eventLat  = (float) $request->input('latitude');
        $eventLng  = (float) $request->input('longitude');
        $radiusKm  = (float) $request->input('radius_km', 5);
        $limit     = (int) $request->input('limit', 5);
        $sortBy    = $request->input('sort_by', 'jarak_jalan');

        $query = Faskes::query()
            ->where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withDistance($eventLat, $eventLng)
            ->withinRadius($eventLat, $eventLng, $radiusKm);

        // Filter Jenis Faskes (dukung string atau array)
        if ($request->has('jenis_faskes')) {
            $jf = $request->input('jenis_faskes');
            if (is_array($jf)) {
                $clean = array_filter($jf, fn ($v) => ! empty($v) && $v !== 'semua');
                if (! empty($clean)) {
                    $query->whereIn('jenis_faskes', $clean);
                }
            } elseif (is_string($jf) && $jf !== '' && $jf !== 'semua') {
                $query->where('jenis_faskes', $jf);
            }
        }

        // Filter Boolean Layanan & Fasilitas
        if ($request->boolean('has_ambulans')) {
            $query->where(function ($q) {
                $q->whereHas('rumahSakitDetail', function ($sub) {
                    $sub->where('ambulans_transport', '>', 0)->orWhere('ambulans_gadar', '>', 0);
                })->orWhereHas('puskesmasDetail', function ($sub) {
                    $sub->where('ambulans_transport', '>', 0)->orWhere('ambulans_roda_dua', '>', 0);
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

        // Terapkan FaskesAttributeFilter
        \App\Support\FaskesAttributeFilter::apply($query, $request);

        // Ambil candidate pool
        $candidatePool = min(max($limit * 3, 15), 30);
        $kandidat = $query->orderBy('jarak_lurus_meter', 'asc')->limit($candidatePool)->get();

        $osrmService = app(OsrmService::class);

        $hasilAnalisis = $kandidat->map(function (Faskes $faskes) use ($eventLat, $eventLng, $osrmService) {
            $jarakLurusMeter = round((float) $faskes->jarak_lurus_meter, 2);
            $jarakLurusKm = round($jarakLurusMeter / 1000, 2);

            $osrmResult = $osrmService->getRoute(
                $eventLat,
                $eventLng,
                (float) $faskes->latitude,
                (float) $faskes->longitude
            );

            $ruteTersedia = (bool) $osrmResult['success'];
            $jarakJalanMeter = $ruteTersedia ? $osrmResult['distance_meters'] : null;
            $jarakJalanKm = $ruteTersedia ? $osrmResult['distance_km'] : null;
            $estimasiDetik = $ruteTersedia ? $osrmResult['duration_seconds'] : null;
            $estimasiMenit = $ruteTersedia ? $osrmResult['duration_minutes'] : null;

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
                'rute_tersedia' => $ruteTersedia,
            ];
        });

        // Sorting
        $hasilSorted = match ($sortBy) {
            'jarak_jalan' => $hasilAnalisis->sortBy([
                fn ($a, $b) => ($a['rute_tersedia'] ? 0 : 1) <=> ($b['rute_tersedia'] ? 0 : 1),
                fn ($a, $b) => ($a['jarak_jalan']['meter'] ?? PHP_FLOAT_MAX) <=> ($b['jarak_jalan']['meter'] ?? PHP_FLOAT_MAX),
            ])->values(),

            'estimasi_waktu' => $hasilAnalisis->sortBy([
                fn ($a, $b) => ($a['rute_tersedia'] ? 0 : 1) <=> ($b['rute_tersedia'] ? 0 : 1),
                fn ($a, $b) => ($a['estimasi_waktu']['detik'] ?? PHP_FLOAT_MAX) <=> ($b['estimasi_waktu']['detik'] ?? PHP_FLOAT_MAX),
            ])->values(),

            'jarak_lurus' => $hasilAnalisis->sortBy('jarak_lurus.meter')->values(),

            default => $hasilAnalisis->values(),
        };

        return $hasilSorted->take($limit)->values()->toArray();
    }
}
