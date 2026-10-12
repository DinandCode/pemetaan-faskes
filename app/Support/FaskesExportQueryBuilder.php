<?php

namespace App\Support;

use App\Models\Faskes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FaskesExportQueryBuilder
{
    /**
     * Membangun query Eloquent untuk daftar Faskes dengan menerapkan SELURUH filter
     * yang didukung di halaman index faskes maupun peta GIS.
     */
    public static function build(Request $request): Builder
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

        // 1. Pencarian teks bebas (search)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', $search)
                  ->orWhere('alamat', 'ilike', $search)
                  ->orWhere('kecamatan', 'ilike', $search)
                  ->orWhere('desa', 'ilike', $search);
            });
        }

        // 2. Filter Jenis Faskes (bisa string tunggal atau array)
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

        // 3. Status Operasional (aktif / nonaktif)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 4. Wilayah Kecamatan
        if ($request->filled('kecamatan')) {
            $query->where('kecamatan', $request->kecamatan);
        }

        // 5. Kepemilikan (Swasta / Pemerintah)
        if ($request->filled('kepemilikan') && in_array($request->kepemilikan, ['Swasta', 'Pemerintah'], true)) {
            $kep = $request->kepemilikan;
            $query->where(function ($q) use ($kep) {
                $q->whereHas('klinikPratamaDetail', fn ($sub) => $sub->where('kepemilikan', 'ilike', $kep))
                  ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('kepemilikan', 'ilike', $kep))
                  ->orWhereHas('laboratoriumDetail', fn ($sub) => $sub->where('kepemilikan', 'ilike', $kep));
            });
        }

        // 6. BPJS
        if ($request->filled('bpjs') && in_array($request->bpjs, ['Ya', 'Tidak'], true)) {
            $isBpjs = $request->bpjs === 'Ya';
            $query->where(function ($q) use ($isBpjs) {
                $q->whereHas('klinikPratamaDetail', fn ($sub) => $sub->where('bpjs', $isBpjs))
                  ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('bpjs', $isBpjs));
            });
        } elseif ($request->boolean('has_bpjs')) {
            $query->where(function ($q) {
                $q->whereHas('klinikPratamaDetail', fn ($sub) => $sub->where('bpjs', true))
                  ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('bpjs', true))
                  ->orWhereIn('jenis_faskes', ['puskesmas', 'rumah_sakit']);
            });
        }

        // 7. Ambulans
        if ($request->filled('has_ambulans') && in_array($request->has_ambulans, ['Ya', 'Tidak'], true)) {
            if ($request->has_ambulans === 'Ya') {
                $query->where(function ($q) {
                    $q->whereHas('rumahSakitDetail', fn ($sub) => $sub->where('ambulans_transport', '>', 0)->orWhere('ambulans_gadar', '>', 0))
                      ->orWhereHas('puskesmasDetail', fn ($sub) => $sub->where('ambulans_transport', '>', 0)->orWhere('ambulans_roda_dua', '>', 0))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->where('ambulans_transport', '>', 0))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('ambulans', '>', 0));
                });
            } else {
                $query->where(function ($q) {
                    $q->whereHas('rumahSakitDetail', fn ($sub) => $sub->where(fn ($s) => $s->whereNull('ambulans_transport')->orWhere('ambulans_transport', '<=', 0))->where(fn ($s) => $s->whereNull('ambulans_gadar')->orWhere('ambulans_gadar', '<=', 0)))
                      ->orWhereHas('puskesmasDetail', fn ($sub) => $sub->where(fn ($s) => $s->whereNull('ambulans_transport')->orWhere('ambulans_transport', '<=', 0))->where(fn ($s) => $s->whereNull('ambulans_roda_dua')->orWhere('ambulans_roda_dua', '<=', 0)))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->whereNull('ambulans_transport')->orWhere('ambulans_transport', '<=', 0))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->whereNull('ambulans')->orWhere('ambulans', '<=', 0));
                });
            }
        } elseif ($request->boolean('has_ambulans')) {
            $query->where(function ($q) {
                $q->whereHas('rumahSakitDetail', fn ($sub) => $sub->where('ambulans_transport', '>', 0)->orWhere('ambulans_gadar', '>', 0))
                  ->orWhereHas('puskesmasDetail', fn ($sub) => $sub->where('ambulans_transport', '>', 0)->orWhere('ambulans_roda_dua', '>', 0))
                  ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->where('ambulans_transport', '>', 0))
                  ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('ambulans', '>', 0));
            });
        }

        // 8. Rawat Inap (Bed)
        if ($request->filled('has_bed') && in_array($request->has_bed, ['Ya', 'Tidak'], true)) {
            if ($request->has_bed === 'Ya') {
                $query->where(function ($q) {
                    $q->where('jenis_faskes', 'rumah_sakit')
                      ->orWhereHas('puskesmasDetail', fn ($sub) => $sub->where('kategori', 'rawat_inap')->orWhere('jumlah_tempat_tidur', '>', 0))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->where('kategori_layanan', 'rawat_inap')->orWhere('bed_rawat_inap', '>', 0))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('kategori_layanan', 'rawat_inap')->orWhere('bed_rawat_inap', '>', 0));
                });
            } else {
                $query->where(function ($q) {
                    $q->whereHas('puskesmasDetail', fn ($sub) => $sub->where('kategori', '!=', 'rawat_inap')->where(fn ($s) => $s->whereNull('jumlah_tempat_tidur')->orWhere('jumlah_tempat_tidur', '<=', 0)))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->where(fn ($s) => $s->whereNull('bed_rawat_inap')->orWhere('bed_rawat_inap', '<=', 0))->where(fn ($s) => $s->whereNull('kategori_layanan')->orWhere('kategori_layanan', '!=', 'rawat_inap')))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where(fn ($s) => $s->whereNull('bed_rawat_inap')->orWhere('bed_rawat_inap', '<=', 0))->where(fn ($s) => $s->whereNull('kategori_layanan')->orWhere('kategori_layanan', '!=', 'rawat_inap')));
                });
            }
        } elseif ($request->boolean('has_rawat_inap')) {
            $query->where(function ($q) {
                $q->where('jenis_faskes', 'rumah_sakit')
                  ->orWhereHas('puskesmasDetail', fn ($sub) => $sub->where('kategori', 'rawat_inap')->orWhere('jumlah_tempat_tidur', '>', 0))
                  ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->where('kategori_layanan', 'rawat_inap')->orWhere('bed_rawat_inap', '>', 0))
                  ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->where('kategori_layanan', 'rawat_inap')->orWhere('bed_rawat_inap', '>', 0));
            });
        }

        // 9. Persalinan / PONED
        $validPersalinan = ['PONED', 'NON PONED (Mampu Salin)', 'NON PONED (Tidak Mampu Salin)'];
        if ($request->filled('persalinan') && in_array($request->persalinan, $validPersalinan, true)) {
            if ($request->persalinan === 'PONED') {
                $query->whereHas('puskesmasDetail', fn ($sub) => $sub->where('poned', 'Ya PONED'));
            } elseif ($request->persalinan === 'NON PONED (Mampu Salin)') {
                $query->whereHas('puskesmasDetail', fn ($sub) => $sub->where('poned', '!=', 'Ya PONED')->where('mampu_salin', 'Ya'));
            } else {
                $query->whereHas('puskesmasDetail', fn ($sub) => $sub->where('poned', '!=', 'Ya PONED')->where(fn ($s) => $s->whereNull('mampu_salin')->orWhere('mampu_salin', '!=', 'Ya')));
            }
        } elseif ($request->boolean('has_poned')) {
            $query->whereHas('puskesmasDetail', fn ($sub) => $sub->where('poned', 'Ya PONED'));
        }

        // 10. Tipe RS
        $validTipeRs = ['A', 'B', 'C', 'D', 'D Pratama'];
        if ($request->filled('tipe_rs') && in_array($request->tipe_rs, $validTipeRs, true)) {
            $query->whereHas('rumahSakitDetail', fn ($sub) => $sub->where('tipe_rs', $request->tipe_rs));
        }

        // 11. Status Izin Operasional
        $validStatusIzin = ['berlaku', 'segera_berakhir', 'kedaluwarsa'];
        if ($request->filled('status_izin') && in_array($request->status_izin, $validStatusIzin, true)) {
            $today = now()->toDateString();
            $threshold = now()->addDays(90)->toDateString();

            if ($request->status_izin === 'berlaku') {
                $query->where(function ($q) use ($threshold) {
                    $q->whereHas('puskesmasDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold))
                      ->orWhereHas('rumahSakitDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold))
                      ->orWhereHas('laboratoriumDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold))
                      ->orWhereHas('griyaSehatDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>', $threshold));
                });
            } elseif ($request->status_izin === 'segera_berakhir') {
                $query->where(function ($q) use ($today, $threshold) {
                    $q->whereHas('puskesmasDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold))
                      ->orWhereHas('rumahSakitDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold))
                      ->orWhereHas('laboratoriumDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold))
                      ->orWhereHas('griyaSehatDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '>=', $today)->whereDate('masa_izin', '<=', $threshold));
                });
            } else {
                $query->where(function ($q) use ($today) {
                    $q->whereHas('puskesmasDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today))
                      ->orWhereHas('rumahSakitDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today))
                      ->orWhereHas('klinikPratamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today))
                      ->orWhereHas('klinikUtamaDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today))
                      ->orWhereHas('laboratoriumDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today))
                      ->orWhereHas('griyaSehatDetail', fn ($sub) => $sub->whereNotNull('masa_izin')->whereDate('masa_izin', '<', $today));
                });
            }
        }

        // 12. FaskesAttributeFilter (Filter Lanjutan Angka/Kategori dari Peta & Index)
        FaskesAttributeFilter::apply($query, $request);

        return $query;
    }
}
