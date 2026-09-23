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

        $faskesList = $query->orderBy('nama', 'asc')->paginate(10)->withQueryString();

        return view('faskes.index', compact('faskesList'));
    }

    /**
     * Menampilkan form tambah faskes baru.
     */
    public function create(): View
    {
        return view('faskes.create');
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

            DB::commit();

            return redirect()->route('faskes.index')->with('success', 'Data faskes berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
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
        ])->findOrFail($id);

        return view('faskes.edit', compact('faskes'));
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

            DB::commit();

            return redirect()->route('faskes.index')->with('success', 'Data faskes berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
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
     * Validasi input utama faskes.
     */
    protected function validateFaskes(Request $request): array
    {
        return $request->validate([
            'nama'          => 'required|string|max:255',
            'jenis_faskes'  => 'required|string|in:puskesmas,rumah_sakit,klinik_pratama,klinik_utama,laboratorium,upkdk',
            'alamat'        => 'nullable|string',
            'kecamatan'     => 'nullable|string|max:255',
            'desa'          => 'nullable|string|max:255',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'nomor_telepon' => 'nullable|string|max:50',
            'status'        => 'required|in:aktif,nonaktif',
        ]);
    }

    /**
     * Menyimpan/memperbarui data child detail sesuai jenis_faskes.
     */
    protected function saveChildDetail(Faskes $faskes, Request $request): void
    {
        switch ($faskes->jenis_faskes) {
            case 'puskesmas':
                $data = [
                    'kategori'            => $request->input('puskesmas_kategori', 'rawat_jalan'),
                    'poned'               => $request->input('puskesmas_poned', 'Tidak PONED'),
                    'mampu_salin'         => $request->input('puskesmas_mampu_salin', 'Ya'),
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
                    'jenis_layanan'      => $request->input('kp_jenis_layanan'),
                    'jumlah_sdm'         => (int) $request->input('kp_jumlah_sdm', 0),
                    'bed_rawat_inap'     => (int) $request->input('kp_bed_rawat_inap', 0),
                    'bpjs'               => $isBpjs,
                    'pj'                 => $request->input('kp_pj'),
                    'kontak_pj'          => $request->input('kp_kontak_pj'),
                ];
                $faskes->klinikPratamaDetail()->updateOrCreate(['faskes_id' => $faskes->id], $data);
                break;

            case 'klinik_utama':
                $data = [
                    'ambulans'          => (int) $request->input('ku_ambulans', 0),
                    'masa_izin'         => $request->input('ku_masa_izin') ?: null,
                    'kemampuan_layanan' => $request->input('ku_kemampuan_layanan'),
                    'kepemilikan'       => $request->input('ku_kepemilikan'),
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
        }
    }
}
