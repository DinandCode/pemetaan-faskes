<?php

namespace App\Http\Controllers;

use App\Models\Faskes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FaskesCrudController extends Controller
{
    /**
     * Menampilkan daftar data faskes.
     */
    public function index(Request $request): View
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

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', $search)
                  ->orWhere('alamat', 'ilike', $search)
                  ->orWhere('kecamatan', 'ilike', $search)
                  ->orWhere('desa', 'ilike', $search);
            });
        }

        if ($request->filled('jenis_faskes')) {
            $query->where('jenis_faskes', $request->jenis_faskes);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // --- FILTER LANJUTAN (TUGAS 3B) ---
        // 1. Kecamatan
        if ($request->filled('kecamatan')) {
            $query->where('kecamatan', $request->kecamatan);
        }

        // 2. Kepemilikan (Swasta / Pemerintah)
        if ($request->filled('kepemilikan') && in_array($request->kepemilikan, ['Swasta', 'Pemerintah'], true)) {
            $kep = $request->kepemilikan;
            $query->where(function ($q) use ($kep) {
                $q->whereHas('klinikPratamaDetail', fn ($sub) => $sub->where('kepemilikan', 'ilike', $kep))
                  ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('kepemilikan', 'ilike', $kep))
                  ->orWhereHas('laboratoriumDetail', fn ($sub) => $sub->where('kepemilikan', 'ilike', $kep));
            });
        }

        // 3. Kerja sama BPJS (Ya / Tidak)
        if ($request->filled('bpjs') && in_array($request->bpjs, ['Ya', 'Tidak'], true)) {
            $isBpjs = $request->bpjs === 'Ya';
            $query->where(function ($q) use ($isBpjs) {
                $q->whereHas('klinikPratamaDetail', fn ($sub) => $sub->where('bpjs', $isBpjs))
                  ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('bpjs', $isBpjs));
            });
        }

        // 4. Memiliki Ambulans (Ya / Tidak)
        if ($request->filled('has_ambulans') && in_array($request->has_ambulans, ['Ya', 'Tidak'], true)) {
            if ($request->has_ambulans === 'Ya') {
                $query->where(function ($q) {
                    $q->whereHas('rumahSakitDetail', fn ($sub) => $sub->where('ambulans_transport', '>', 0)->orWhere('ambulans_gadar', '>', 0))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->where('ambulans_transport', '>', 0))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('ambulans', '>', 0));
                });
            } else {
                $query->where(function ($q) {
                    $q->whereHas('rumahSakitDetail', fn ($sub) => $sub->where(fn ($s) => $s->whereNull('ambulans_transport')->orWhere('ambulans_transport', '<=', 0))->where(fn ($s) => $s->whereNull('ambulans_gadar')->orWhere('ambulans_gadar', '<=', 0)))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->whereNull('ambulans_transport')->orWhere('ambulans_transport', '<=', 0))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->whereNull('ambulans')->orWhere('ambulans', '<=', 0));
                });
            }
        }

        // 5. Memiliki Bed Rawat Inap (Ya / Tidak)
        if ($request->filled('has_bed') && in_array($request->has_bed, ['Ya', 'Tidak'], true)) {
            if ($request->has_bed === 'Ya') {
                $query->where(function ($q) {
                    $q->where('jenis_faskes', 'rumah_sakit')
                      ->orWhereHas('puskesmasDetail', fn ($sub) => $sub->where('kategori', 'rawat_inap'))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->where('kategori_layanan', 'rawat_inap')->orWhere('bed_rawat_inap', '>', 0))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('kategori_layanan', 'rawat_inap')->orWhere('bed_rawat_inap', '>', 0));
                });
            } else {
                $query->where(function ($q) {
                    $q->whereHas('puskesmasDetail', fn ($sub) => $sub->where('kategori', '!=', 'rawat_inap'))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->where(fn ($s) => $s->whereNull('bed_rawat_inap')->orWhere('bed_rawat_inap', '<=', 0))->where(fn ($s) => $s->whereNull('kategori_layanan')->orWhere('kategori_layanan', '!=', 'rawat_inap')))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where(fn ($s) => $s->whereNull('bed_rawat_inap')->orWhere('bed_rawat_inap', '<=', 0))->where(fn ($s) => $s->whereNull('kategori_layanan')->orWhere('kategori_layanan', '!=', 'rawat_inap')));
                });
            }
        }

        // 6. Kategori Kemampuan Persalinan Puskesmas
        $validPersalinan = ['PONED', 'NON PONED (Mampu Salin)', 'NON PONED (Tidak Mampu Salin)'];
        if ($request->filled('persalinan') && in_array($request->persalinan, $validPersalinan, true)) {
            if ($request->persalinan === 'PONED') {
                $query->whereHas('puskesmasDetail', fn ($sub) => $sub->where('poned', 'Ya PONED'));
            } elseif ($request->persalinan === 'NON PONED (Mampu Salin)') {
                $query->whereHas('puskesmasDetail', fn ($sub) => $sub->where('poned', '!=', 'Ya PONED')->where('mampu_salin', 'Ya'));
            } else {
                $query->whereHas('puskesmasDetail', fn ($sub) => $sub->where('poned', '!=', 'Ya PONED')->where(fn ($s) => $s->whereNull('mampu_salin')->orWhere('mampu_salin', '!=', 'Ya')));
            }
        }

        // 7. Tipe RS
        $validTipeRs = ['A', 'B', 'C', 'D', 'D Pratama'];
        if ($request->filled('tipe_rs') && in_array($request->tipe_rs, $validTipeRs, true)) {
            $query->whereHas('rumahSakitDetail', fn ($sub) => $sub->where('tipe_rs', $request->tipe_rs));
        }

        // 8. Status Izin Operasional
        $validStatusIzin = ['berlaku', 'segera_berakhir', 'kedaluwarsa'];
        if ($request->filled('status_izin') && in_array($request->status_izin, $validStatusIzin, true)) {
            $today = now()->toDateString();
            $threshold = now()->addDays(90)->toDateString();

            if ($request->status_izin === 'berlaku') {
                $query->where(function ($q) use ($threshold) {
                    $q->whereHas('puskesmasDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold))
                      ->orWhereHas('laboratoriumDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold))
                      ->orWhereHas('griyaSehatDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold));
                });
            } elseif ($request->status_izin === 'segera_berakhir') {
                $query->where(function ($q) use ($today, $threshold) {
                    $q->whereHas('puskesmasDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold))
                      ->orWhereHas('laboratoriumDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold))
                      ->orWhereHas('griyaSehatDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold));
                });
            } else {
                $query->where(function ($q) use ($today) {
                    $q->whereHas('puskesmasDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today))
                      ->orWhereHas('laboratoriumDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today))
                      ->orWhereHas('griyaSehatDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today));
                });
            }
        }

        // 9. Kolom Tambahan Dinamis
        if ($request->has('custom_filter') && is_array($request->custom_filter)) {
            foreach ($request->custom_filter as $defId => $val) {
                if ($val !== null && trim((string) $val) !== '') {
                    $query->whereHas('fieldValues', function ($sub) use ($defId, $val) {
                        $sub->where('field_definition_id', (int) $defId)->where('value', trim((string) $val));
                    });
                }
            }
        }

        $faskesList = $query->orderBy('nama', 'asc')->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('faskes._table', compact('faskesList'));
        }

        $kecamatanList = Faskes::whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->distinct()
            ->orderBy('kecamatan')
            ->pluck('kecamatan');

        $activeFieldDefinitions = \App\Models\FaskesFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('faskes.index', compact('faskesList', 'kecamatanList', 'activeFieldDefinitions'));
    }

    /**
     * Menampilkan form tambah faskes baru.
     */
    public function create(): View
    {
        $customFieldDefinitions = \App\Models\FaskesFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('faskes.create', compact('customFieldDefinitions'));
    }

    /**
     * Menyimpan data faskes baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateFaskes($request);

        DB::beginTransaction();
        try {
            $faskes = new Faskes();
            $faskes->fill($validated);
            
            // Set PostGIS point geometry
            $faskes->lokasi = DB::raw("ST_SetSRID(ST_MakePoint({$validated['longitude']}, {$validated['latitude']}), 4326)");
            $faskes->save();

            // Simpan detail child
            $this->saveChildDetail($faskes, $request);

            // Simpan kolom tambahan (custom fields)
            $this->saveCustomFields($faskes, $request);

            DB::commit();

            return redirect()->route('faskes.index')->with('success', 'Data faskes berhasil ditambahkan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal simpan faskes: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menyimpan faskes: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form edit faskes.
     */
    public function edit(int $id): View
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

        $customFieldDefinitions = \App\Models\FaskesFieldDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        $existingCustomValues = $faskes->fieldValues->pluck('value', 'field_definition_id')->toArray();

        return view('faskes.edit', compact('faskes', 'customFieldDefinitions', 'existingCustomValues'));
    }

    /**
     * Memperbarui data faskes.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $faskes = Faskes::findOrFail($id);
        $oldJenis = $faskes->jenis_faskes;

        $validated = $this->validateFaskes($request);

        DB::beginTransaction();
        try {
            $faskes->fill($validated);
            
            // Update PostGIS point geometry
            $faskes->lokasi = DB::raw("ST_SetSRID(ST_MakePoint({$validated['longitude']}, {$validated['latitude']}), 4326)");
            $faskes->save();

            // Jika jenis faskes berubah, hapus relasi child yang lama
            if ($oldJenis !== $validated['jenis_faskes']) {
                $this->deleteOldChildDetail($faskes, $oldJenis);
            }

            // Simpan atau update child detail yang aktif
            $this->saveChildDetail($faskes, $request);

            // Simpan kolom tambahan (custom fields)
            $this->saveCustomFields($faskes, $request);

            DB::commit();

            return redirect()->route('faskes.index')->with('success', 'Data faskes berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal simpan faskes: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal memperbarui faskes: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus faskes (relasi detail child terhapus cascade).
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $faskes = Faskes::findOrFail($id);
            $faskes->delete();

            return redirect()->route('faskes.index')->with('success', 'Faskes berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus faskes: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus beberapa faskes sekaligus (massal - Tugas 3C).
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:faskes,id',
        ], [
            'ids.required' => 'Pilih minimal satu faskes untuk dihapus.',
            'ids.min'      => 'Pilih minimal satu faskes untuk dihapus.',
            'ids.max'      => 'Maksimal 100 faskes yang dapat dihapus sekaligus.',
            'ids.*.exists' => 'Data faskes tidak ditemukan di sistem.',
        ]);

        DB::beginTransaction();
        try {
            $count = Faskes::whereIn('id', $validated['ids'])->delete();
            DB::commit();

            $message = "{$count} faskes berhasil dihapus.";

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'count'   => $count,
                    'message' => $message,
                ]);
            }

            return redirect()->route('faskes.index', $request->query())
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal hapus massal faskes: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus faskes terpilih: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus faskes terpilih: ' . $e->getMessage());
        }
    }

    /**
     * Validasi input utama faskes.
     */
    protected function validateFaskes(Request $request): array
    {
        return $request->validate([
            'nama'                  => 'required|string|max:255',
            'jenis_faskes'          => 'required|string|in:puskesmas,rumah_sakit,klinik_pratama,klinik_utama,laboratorium,upkdk,griya_sehat,tpmd,tpmdg,tpmb,tpmp',
            'alamat'                => 'nullable|string',
            'kecamatan'             => 'nullable|string|max:255',
            'desa'                  => 'nullable|string|max:255',
            'latitude'              => 'required|numeric|between:-90,90',
            'longitude'             => 'required|numeric|between:-180,180',
            'nomor_telepon'         => 'nullable|string|max:50',
            'status'                => 'required|in:aktif,nonaktif',
            // Puskesmas
            'puskesmas_kemampuan_persalinan' => 'nullable|string|in:PONED,NON PONED (Mampu Salin),NON PONED (Tidak Mampu Salin)',
            // Rumah Sakit
            'rs_tipe_rs'            => 'nullable|string|in:A,B,C,D,D Pratama',
            'rs_ambulans_transport' => 'nullable|integer|min:0',
            'rs_ambulans_gadar'     => 'nullable|integer|min:0',
            // Klinik Pratama
            'kp_kategori_layanan'   => 'nullable|string|in:rawat_jalan,rawat_inap',
            'kp_kepemilikan'        => 'nullable|string|in:Swasta,Pemerintah',
            // Klinik Utama
            'ku_kategori_layanan'   => 'nullable|string|in:rawat_jalan,rawat_inap',
            'ku_bed_rawat_inap'     => 'nullable|integer|min:0',
            'ku_bpjs'               => 'nullable|string|in:Ya,Tidak,0,1',
            'ku_pj'                 => 'nullable|string|max:255',
            'ku_kontak_pj'          => 'nullable|string|max:100',
            'ku_kepemilikan'        => 'nullable|string|in:Swasta,Pemerintah',
            // Laboratorium
            'lab_kepemilikan'       => 'nullable|string|in:Swasta,Pemerintah',
            // Griya Sehat
            'gs_masa_izin'          => 'nullable|date',
            'gs_pj'                 => 'nullable|string|max:255',
            'gs_kontak_pj'          => 'nullable|string|max:100',
            'gs_jumlah_sdm'         => 'nullable|integer|min:0',
        ]);
    }

    /**
     * Menyimpan/memperbarui data child detail sesuai jenis_faskes.
     */
    protected function saveChildDetail(Faskes $faskes, Request $request): void
    {
        switch ($faskes->jenis_faskes) {
            case 'puskesmas':
                $persalinan = $request->input('puskesmas_kemampuan_persalinan');
                $poned = 'Tidak PONED';
                $mampuSalin = 'Tidak';
                if ($persalinan === 'PONED') {
                    $poned = 'Ya PONED';
                    $mampuSalin = 'Ya';
                } elseif ($persalinan === 'NON PONED (Mampu Salin)') {
                    $poned = 'Tidak PONED';
                    $mampuSalin = 'Ya';
                } elseif ($persalinan === 'NON PONED (Tidak Mampu Salin)') {
                    $poned = 'Tidak PONED';
                    $mampuSalin = 'Tidak';
                } else {
                    $poned = $request->input('puskesmas_poned', 'Tidak PONED');
                    $mampuSalin = $request->input('puskesmas_mampu_salin', 'Ya');
                }

                $data = [
                    'kategori'            => $request->input('puskesmas_kategori', 'rawat_jalan'),
                    'poned'               => $poned,
                    'mampu_salin'         => $mampuSalin,
                    'jumlah_tempat_tidur' => (int) $request->input('puskesmas_jumlah_tempat_tidur', 0),
                    'ambulans_transport'  => (int) $request->input('puskesmas_ambulans_transport', 0),
                    'ambulans_roda_dua'   => (int) $request->input('puskesmas_ambulans_roda_dua', 0),
                    'masa_izin'           => $request->input('puskesmas_masa_izin') ?: null,
                    'jumlah_sdm'          => (int) $request->input('puskesmas_jumlah_sdm', 0),
                    'wilayah'             => $request->input('puskesmas_wilayah', 'perkotaan'),
                ];
                $faskes->puskesmasDetail()->updateOrCreate(['faskes_id' => $faskes->id], $data);
                break;

            case 'rumah_sakit':
                $data = [
                    'ambulans_transport'  => (int) $request->input('rs_ambulans_transport', 0),
                    'ambulans_gadar'      => (int) $request->input('rs_ambulans_gadar', 0),
                    'ponek'               => $request->input('rs_ponek', 'Tidak PONEK'),
                    'kemampuan_pelayanan' => $request->input('rs_kemampuan_pelayanan'),
                    'tipe_rs'             => $request->input('rs_tipe_rs') ?: null,
                    'masa_izin'           => $request->input('rs_masa_izin') ?: null,
                ];
                $faskes->rumahSakitDetail()->updateOrCreate(['faskes_id' => $faskes->id], $data);
                break;

            case 'klinik_pratama':
                $bpjsInput = $request->input('kp_bpjs');
                $isBpjs = ($bpjsInput === 'Ya' || $bpjsInput === '1' || $bpjsInput === true);

                $data = [
                    'ambulans_transport' => (int) $request->input('kp_ambulans_transport', 0),
                    'masa_izin'          => $request->input('kp_masa_izin') ?: null,
                    'kategori_layanan'   => $request->input('kp_kategori_layanan') ?: 'rawat_jalan',
                    'jenis_layanan'      => $request->input('kp_jenis_layanan'),
                    'jumlah_sdm'         => (int) $request->input('kp_jumlah_sdm', 0),
                    'bed_rawat_inap'     => (int) $request->input('kp_bed_rawat_inap', 0),
                    'bpjs'               => $isBpjs,
                    'kepemilikan'        => $request->input('kp_kepemilikan') ?: 'Swasta',
                    'pj'                 => $request->input('kp_pj'),
                    'kontak_pj'          => $request->input('kp_kontak_pj'),
                ];
                $faskes->klinikPratamaDetail()->updateOrCreate(['faskes_id' => $faskes->id], $data);
                break;

            case 'klinik_utama':
                $kuBpjsInput = $request->input('ku_bpjs');
                $isKuBpjs = ($kuBpjsInput === 'Ya' || $kuBpjsInput === '1' || $kuBpjsInput === true);

                $data = [
                    'ambulans'          => (int) $request->input('ku_ambulans', 0),
                    'masa_izin'         => $request->input('ku_masa_izin') ?: null,
                    'kemampuan_layanan' => $request->input('ku_kemampuan_layanan'),
                    'kategori_layanan'  => $request->input('ku_kategori_layanan') ?: 'rawat_jalan',
                    'bed_rawat_inap'    => (int) $request->input('ku_bed_rawat_inap', 0),
                    'bpjs'              => $isKuBpjs,
                    'kepemilikan'       => $request->input('ku_kepemilikan'),
                    'pj'                => $request->input('ku_pj'),
                    'kontak_pj'         => $request->input('ku_kontak_pj'),
                ];
                $faskes->klinikUtamaDetail()->updateOrCreate(['faskes_id' => $faskes->id], $data);
                break;

            case 'laboratorium':
                $data = [
                    'masa_izin'     => $request->input('lab_masa_izin') ?: null,
                    'jenis_layanan' => $request->input('lab_jenis_layanan'),
                    'kepemilikan'   => $request->input('lab_kepemilikan'),
                ];
                $faskes->laboratoriumDetail()->updateOrCreate(['faskes_id' => $faskes->id], $data);
                break;

            case 'upkdk':
                $data = [
                    'is_pustu'   => $request->input('upkdk_is_pustu', 'Tidak'),
                    'is_pkd'     => $request->input('upkdk_is_pkd', 'Tidak'),
                    'jumlah_sdm' => (int) $request->input('upkdk_jumlah_sdm', 0),
                ];
                $faskes->upkdkDetail()->updateOrCreate(['faskes_id' => $faskes->id], $data);
                break;

            case 'griya_sehat':
                $data = [
                    'masa_izin'  => $request->input('gs_masa_izin') ?: null,
                    'pj'         => $request->input('gs_pj'),
                    'kontak_pj'  => $request->input('gs_kontak_pj'),
                    'jumlah_sdm' => (int) $request->input('gs_jumlah_sdm', 0),
                ];
                $faskes->griyaSehatDetail()->updateOrCreate(['faskes_id' => $faskes->id], $data);
                break;
        }
    }

    /**
     * Hapus relasi detail jika tipe jenis_faskes diganti saat update.
     */
    protected function deleteOldChildDetail(Faskes $faskes, string $oldJenis): void
    {
        switch ($oldJenis) {
            case 'puskesmas':
                $faskes->puskesmasDetail()->delete();
                break;
            case 'rumah_sakit':
                $faskes->rumahSakitDetail()->delete();
                break;
            case 'klinik_pratama':
                $faskes->klinikPratamaDetail()->delete();
                break;
            case 'klinik_utama':
                $faskes->klinikUtamaDetail()->delete();
                break;
            case 'laboratorium':
                $faskes->laboratoriumDetail()->delete();
                break;
            case 'upkdk':
                $faskes->upkdkDetail()->delete();
                break;
            case 'griya_sehat':
                $faskes->griyaSehatDetail()->delete();
                break;
        }
    }

    /**
     * Menyimpan nilai kolom tambahan (custom fields).
     */
    protected function saveCustomFields(Faskes $faskes, Request $request): void
    {
        $customInputs = $request->input('custom', []);
        if (! is_array($customInputs)) {
            $customInputs = [];
        }

        // Ambil definisi aktif yang berlaku untuk jenis faskes ini (atau berlaku untuk semua jenis)
        $definitions = \App\Models\FaskesFieldDefinition::where('is_active', true)
            ->where(function ($q) use ($faskes) {
                $q->whereNull('jenis_faskes')
                  ->orWhere('jenis_faskes', $faskes->jenis_faskes);
            })
            ->get();

        $errors = [];

        foreach ($definitions as $def) {
            $rawVal = $customInputs[$def->id] ?? null;
            $res = $this->processCustomFieldValue($def, $rawVal, $faskes->id);

            if ($res['error'] !== null) {
                $errors["custom.{$def->id}"] = $res['error'];
                continue;
            }

            if ($res['is_empty']) {
                \App\Models\FaskesFieldValue::where('faskes_id', $faskes->id)
                    ->where('field_definition_id', $def->id)
                    ->delete();
                continue;
            }

            \App\Models\FaskesFieldValue::updateOrCreate(
                [
                    'faskes_id' => $faskes->id,
                    'field_definition_id' => $def->id,
                ],
                [
                    'value' => $res['value'],
                    'value_number' => $res['value_number'],
                    'value_date' => $res['value_date'],
                    'value_bool' => $res['value_bool'],
                    'value_json' => $res['value_json'],
                ]
            );
        }

        if (! empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    /**
     * Memproses dan memvalidasi nilai satu kolom tambahan.
     */
    protected function processCustomFieldValue(\App\Models\FaskesFieldDefinition $def, mixed $rawVal, int $faskesId): array
    {
        $result = [
            'is_empty' => false,
            'error' => null,
            'value' => null,
            'value_number' => null,
            'value_date' => null,
            'value_bool' => null,
            'value_json' => null,
        ];

        $cfg = $def->getConfigArray();
        $type = $def->type;

        $isEmpty = false;
        if ($rawVal === null || $rawVal === '') {
            $isEmpty = true;
        } elseif (is_array($rawVal) && count(array_filter($rawVal, fn ($v) => $v !== null && $v !== '')) === 0) {
            $isEmpty = true;
        }

        if ($def->is_required && $isEmpty) {
            $result['error'] = "Kolom '{$def->label}' wajib diisi.";
            return $result;
        }

        if ($isEmpty) {
            $result['is_empty'] = true;
            return $result;
        }

        switch ($type) {
            case \App\Models\FaskesFieldDefinition::TYPE_TEXT:
                $strVal = trim((string) $rawVal);
                $maxLen = (int) ($cfg['max_length'] ?? 255);
                if (mb_strlen($strVal) > $maxLen) {
                    $result['error'] = "Kolom '{$def->label}' maksimal {$maxLen} karakter.";
                    return $result;
                }
                $result['value'] = $strVal;
                break;

            case \App\Models\FaskesFieldDefinition::TYPE_TEXTAREA:
                $strVal = trim((string) $rawVal);
                $maxLen = (int) ($cfg['max_length'] ?? 2000);
                if (mb_strlen($strVal) > $maxLen) {
                    $result['error'] = "Kolom '{$def->label}' maksimal {$maxLen} karakter.";
                    return $result;
                }
                $result['value'] = $strVal;
                break;

            case \App\Models\FaskesFieldDefinition::TYPE_NUMBER:
                $numStr = str_replace(',', '.', trim((string) $rawVal));
                if (! is_numeric($numStr)) {
                    $result['error'] = "Kolom '{$def->label}' harus berupa angka valid.";
                    return $result;
                }
                $floatVal = (float) $numStr;
                if (isset($cfg['min']) && $cfg['min'] !== null && $floatVal < (float) $cfg['min']) {
                    $result['error'] = "Kolom '{$def->label}' minimal {$cfg['min']}.";
                    return $result;
                }
                if (isset($cfg['max']) && $cfg['max'] !== null && $floatVal > (float) $cfg['max']) {
                    $result['error'] = "Kolom '{$def->label}' maksimal {$cfg['max']}.";
                    return $result;
                }
                $decimals = (int) ($cfg['decimals'] ?? 0);
                $decimals = max(0, min(4, $decimals));
                $result['value'] = number_format($floatVal, $decimals, '.', '');
                $result['value_number'] = $floatVal;
                break;

            case \App\Models\FaskesFieldDefinition::TYPE_DATE:
                $dateStr = trim((string) $rawVal);
                try {
                    $parsedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $dateStr);
                    if (! $parsedDate || $parsedDate->format('Y-m-d') !== $dateStr) {
                        throw new \Exception();
                    }
                } catch (\Throwable $e) {
                    $result['error'] = "Format tanggal pada kolom '{$def->label}' tidak valid (YYYY-MM-DD).";
                    return $result;
                }
                $result['value'] = $dateStr;
                $result['value_date'] = $dateStr;
                break;

            case \App\Models\FaskesFieldDefinition::TYPE_BOOLEAN:
                $boolStr = strtolower(trim((string) $rawVal));
                if (in_array($boolStr, ['1', 'true', 'ya', 'yes'], true)) {
                    $result['value'] = 'Ya';
                    $result['value_bool'] = true;
                } elseif (in_array($boolStr, ['0', 'false', 'tidak', 'no'], true)) {
                    $result['value'] = 'Tidak';
                    $result['value_bool'] = false;
                } else {
                    $result['is_empty'] = true;
                }
                break;

            case \App\Models\FaskesFieldDefinition::TYPE_SELECT:
                $strVal = trim((string) $rawVal);
                $allowedOptions = (array) ($def->options ?? []);
                $existingVal = \App\Models\FaskesFieldValue::where('faskes_id', $faskesId)
                    ->where('field_definition_id', $def->id)
                    ->value('value');
                if ($existingVal !== null && $existingVal !== '') {
                    $allowedOptions[] = $existingVal;
                }

                if (! in_array($strVal, $allowedOptions, true)) {
                    $result['error'] = "Pilihan untuk kolom '{$def->label}' tidak valid.";
                    return $result;
                }
                $result['value'] = $strVal;
                break;

            case \App\Models\FaskesFieldDefinition::TYPE_MULTISELECT:
                $selectedArray = is_array($rawVal) ? $rawVal : array_map('trim', explode(';', (string) $rawVal));
                $selectedArray = array_values(array_unique(array_filter($selectedArray, fn ($o) => trim((string) $o) !== '')));

                if (empty($selectedArray)) {
                    if ($def->is_required) {
                        $result['error'] = "Kolom '{$def->label}' wajib diisi.";
                        return $result;
                    }
                    $result['is_empty'] = true;
                    return $result;
                }

                $maxSelect = isset($cfg['max_select']) ? (int) $cfg['max_select'] : null;
                if ($maxSelect && count($selectedArray) > $maxSelect) {
                    $result['error'] = "Maksimal {$maxSelect} pilihan untuk kolom '{$def->label}'.";
                    return $result;
                }

                $allowedOptions = (array) ($def->options ?? []);
                $existingValues = \App\Models\FaskesFieldValue::where('faskes_id', $faskesId)
                    ->where('field_definition_id', $def->id)
                    ->value('value_json');
                if (is_array($existingValues)) {
                    $allowedOptions = array_merge($allowedOptions, $existingValues);
                }

                foreach ($selectedArray as $item) {
                    if (! in_array($item, $allowedOptions, true)) {
                        $result['error'] = "Pilihan '{$item}' pada kolom '{$def->label}' tidak valid.";
                        return $result;
                    }
                }

                $result['value'] = implode('; ', $selectedArray);
                $result['value_json'] = $selectedArray;
                break;

            case \App\Models\FaskesFieldDefinition::TYPE_URL:
                $urlVal = trim((string) $rawVal);
                if (! filter_var($urlVal, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $urlVal)) {
                    $result['error'] = "Kolom '{$def->label}' harus berupa tautan URL valid (awali dengan http:// atau https://).";
                    return $result;
                }
                $result['value'] = $urlVal;
                break;

            case \App\Models\FaskesFieldDefinition::TYPE_EMAIL:
                $emailVal = trim((string) $rawVal);
                if (! filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
                    $result['error'] = "Kolom '{$def->label}' harus berupa alamat email yang valid.";
                    return $result;
                }
                $result['value'] = $emailVal;
                break;

            case \App\Models\FaskesFieldDefinition::TYPE_PHONE:
                $phoneVal = trim((string) $rawVal);
                if (! preg_match('/^[0-9+()\-\s]{6,25}$/', $phoneVal)) {
                    $result['error'] = "Kolom '{$def->label}' harus berupa nomor telepon yang valid.";
                    return $result;
                }
                $result['value'] = $phoneVal;
                break;

            default:
                $result['value'] = trim((string) $rawVal);
                break;
        }

        return $result;
    }
}
