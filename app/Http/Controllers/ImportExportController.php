<?php

namespace App\Http\Controllers;

use App\Exports\AnalisisEventExport;
use App\Exports\FaskesPerKecamatanExport;
use App\Imports\FaskesImport;
use App\Models\Faskes;
use App\Services\OsrmService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    /**
     * Import data Faskes dari file Excel Dinkes.
     */
    public function importFaskes(Request $request)
    {
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
            return redirect()->route('faskes.index')->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    /**
     * Export rekapitulasi seluruh Faskes per Kecamatan ke Excel.
     */
    public function exportFaskesExcel(Request $request)
    {
        $kecamatan = $request->query('kecamatan');
        $jenisFaskes = $request->query('jenis_faskes');

        $filename = 'rekap_faskes_banyumas_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new FaskesPerKecamatanExport($kecamatan, $jenisFaskes), $filename);
    }

    /**
     * Export rekapitulasi seluruh Faskes per Kecamatan ke PDF.
     */
    public function exportFaskesPdf(Request $request)
    {
        $query = Faskes::query()->with([
            'puskesmasDetail', 'rumahSakitDetail', 'klinikPratamaDetail',
            'klinikUtamaDetail', 'laboratoriumDetail', 'upkdkDetail',
        ]);

        if ($request->filled('kecamatan')) {
            $query->where('kecamatan', $request->query('kecamatan'));
        }
        if ($request->filled('jenis_faskes')) {
            $query->where('jenis_faskes', $request->query('jenis_faskes'));
        }

        $faskesList = $query->orderBy('kecamatan')->orderBy('jenis_faskes')->orderBy('nama')->get();

        $pdf = Pdf::loadView('exports.faskes-rekap-pdf', compact('faskesList'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('rekap_faskes_banyumas_' . date('Ymd_His') . '.pdf');
    }

    /**
     * Export hasil analisis event ke Excel.
     */
    public function exportAnalisisExcel(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1',
            'jenis_faskes' => 'nullable|string|in:puskesmas,rumah_sakit,klinik_pratama,klinik_utama,laboratorium,upkdk',
            'limit'     => 'nullable|integer|min:1|max:20',
        ]);

        $data = $this->runAnalisis($request);

        $parameter = [
            'latitude'     => $request->input('latitude'),
            'longitude'    => $request->input('longitude'),
            'radius_km'    => $request->input('radius_km', 5),
            'jenis_faskes' => $request->input('jenis_faskes', 'semua'),
        ];

        $filename = 'analisis_event_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new AnalisisEventExport($data, $parameter), $filename);
    }

    /**
     * Export hasil analisis event ke PDF.
     */
    public function exportAnalisisPdf(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1',
            'jenis_faskes' => 'nullable|string|in:puskesmas,rumah_sakit,klinik_pratama,klinik_utama,laboratorium,upkdk',
            'limit'     => 'nullable|integer|min:1|max:20',
        ]);

        $data = $this->runAnalisis($request);

        $parameter = [
            'latitude'     => $request->input('latitude'),
            'longitude'    => $request->input('longitude'),
            'radius_km'    => $request->input('radius_km', 5),
            'jenis_faskes' => $request->input('jenis_faskes', 'semua'),
        ];

        $pdf = Pdf::loadView('exports.analisis-event-pdf', compact('data', 'parameter'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('analisis_event_' . date('Ymd_His') . '.pdf');
    }

    /**
     * Menjalankan logika spasial & routing OSRM untuk analisis event.
     */
    protected function runAnalisis(Request $request): array
    {
        $eventLat  = (float) $request->input('latitude');
        $eventLng  = (float) $request->input('longitude');
        $radiusKm  = (float) $request->input('radius_km', 5);
        $limit     = (int) $request->input('limit', 5);

        $query = Faskes::query()
            ->where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withDistance($eventLat, $eventLng)
            ->withinRadius($eventLat, $eventLng, $radiusKm);

        if ($request->filled('jenis_faskes') && $request->input('jenis_faskes') !== 'semua') {
            $query->where('jenis_faskes', $request->input('jenis_faskes'));
        }

        $kandidat = $query->orderBy('jarak_lurus_meter', 'asc')->limit($limit)->get();

        $osrmService = app(OsrmService::class);

        return $kandidat->map(function (Faskes $faskes) use ($eventLat, $eventLng, $osrmService) {
            $jarakLurusMeter = round((float) $faskes->jarak_lurus_meter, 2);
            $jarakLurusKm = round($jarakLurusMeter / 1000, 2);

            $osrmResult = $osrmService->getRoute($eventLat, $eventLng, (float) $faskes->latitude, (float) $faskes->longitude);

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
                    'meter' => $osrmResult['success'] ? $osrmResult['distance_meters'] : $jarakLurusMeter,
                    'km'    => $osrmResult['success'] ? $osrmResult['distance_km'] : $jarakLurusKm,
                ],
                'estimasi_waktu' => [
                    'detik' => $osrmResult['success'] ? $osrmResult['duration_seconds'] : 0.0,
                    'menit' => $osrmResult['success'] ? $osrmResult['duration_minutes'] : 0.0,
                ],
                'rute_tersedia' => $osrmResult['success'],
            ];
        })->sortBy('jarak_jalan.meter')->values()->toArray();
    }
}
