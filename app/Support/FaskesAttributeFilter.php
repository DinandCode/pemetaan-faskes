<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FaskesAttributeFilter
{
    /**
     * Menerapkan filter atribut lanjutan ke query Eloquent faskes.
     */
    public static function apply(Builder $query, Request $request): Builder
    {
        // =========================================================================
        // 1. FILTER JUMLAH (Angka Minimum)
        // =========================================================================

        // 1a. Total Ambulans Minimal (semua jenis yang punya ambulans)
        // Definisi total per jenis:
        // - Puskesmas: ambulans_transport + ambulans_roda_dua
        // - Rumah Sakit: ambulans_transport + ambulans_gadar
        // - Klinik Pratama: ambulans_transport
        // - Klinik Utama: ambulans
        if ($request->filled('min_total_ambulans')) {
            $min = max(0, min(100, $request->integer('min_total_ambulans')));
            if ($min > 0) {
                $query->where(function ($q) use ($min) {
                    $q->whereHas('puskesmasDetail', function ($sub) use ($min) {
                        $sub->whereRaw('(COALESCE(ambulans_transport, 0) + COALESCE(ambulans_roda_dua, 0)) >= ?', [$min]);
                    })->orWhereHas('rumahSakitDetail', function ($sub) use ($min) {
                        $sub->whereRaw('(COALESCE(ambulans_transport, 0) + COALESCE(ambulans_gadar, 0)) >= ?', [$min]);
                    })->orWhereHas('klinikPratamaDetail', function ($sub) use ($min) {
                        $sub->whereRaw('COALESCE(ambulans_transport, 0) >= ?', [$min]);
                    })->orWhereHas('klinikUtamaDetail', function ($sub) use ($min) {
                        $sub->whereRaw('COALESCE(ambulans, 0) >= ?', [$min]);
                    });
                });
            }
        }

        // 1b. Ambulans Gadar Minimal (Rumah Sakit)
        if ($request->filled('min_ambulans_gadar')) {
            $min = max(0, min(50, $request->integer('min_ambulans_gadar')));
            if ($min > 0) {
                $query->whereHas('rumahSakitDetail', function ($sub) use ($min) {
                    $sub->where('ambulans_gadar', '>=', $min);
                });
            }
        }

        // 1c. Ambulans Roda Dua Minimal (Puskesmas)
        if ($request->filled('min_ambulans_roda_dua')) {
            $min = max(0, min(50, $request->integer('min_ambulans_roda_dua')));
            if ($min > 0) {
                $query->whereHas('puskesmasDetail', function ($sub) use ($min) {
                    $sub->where('ambulans_roda_dua', '>=', $min);
                });
            }
        }

        // 1d. Jumlah TT (Tempat Tidur) Rawat Inap Minimal
        // Relevan untuk: puskesmas (jumlah_tempat_tidur), klinik_pratama (bed_rawat_inap), klinik_utama (bed_rawat_inap)
        // (Rumah sakit belum memiliki kolom jumlah TT di database).
        if ($request->filled('min_bed')) {
            $min = max(0, min(500, $request->integer('min_bed')));
            if ($min > 0) {
                $query->where(function ($q) use ($min) {
                    $q->whereHas('puskesmasDetail', function ($sub) use ($min) {
                        $sub->where('jumlah_tempat_tidur', '>=', $min);
                    })->orWhereHas('klinikPratamaDetail', function ($sub) use ($min) {
                        $sub->where('bed_rawat_inap', '>=', $min);
                    })->orWhereHas('klinikUtamaDetail', function ($sub) use ($min) {
                        $sub->where('bed_rawat_inap', '>=', $min);
                    });
                });
            }
        }

        // 1e. Jumlah SDM Minimal
        // Relevan untuk: puskesmas, klinik_pratama, upkdk, griya_sehat
        if ($request->filled('min_sdm')) {
            $min = max(0, min(1000, $request->integer('min_sdm')));
            if ($min > 0) {
                $query->where(function ($q) use ($min) {
                    $q->whereHas('puskesmasDetail', function ($sub) use ($min) {
                        $sub->where('jumlah_sdm', '>=', $min);
                    })->orWhereHas('klinikPratamaDetail', function ($sub) use ($min) {
                        $sub->where('jumlah_sdm', '>=', $min);
                    })->orWhereHas('upkdkDetail', function ($sub) use ($min) {
                        $sub->where('jumlah_sdm', '>=', $min);
                    })->orWhereHas('griyaSehatDetail', function ($sub) use ($min) {
                        $sub->where('jumlah_sdm', '>=', $min);
                    });
                });
            }
        }

        // =========================================================================
        // 2. FILTER KATEGORI
        // =========================================================================

        // 2a. Kepemilikan: Swasta / Pemerintah (klinik_pratama, klinik_utama, laboratorium)
        if ($request->filled('kepemilikan')) {
            $val = trim((string) $request->input('kepemilikan'));
            $allowed = ['Swasta', 'Pemerintah'];
            if (in_array($val, $allowed, true)) {
                $query->where(function ($q) use ($val) {
                    $q->whereHas('klinikPratamaDetail', fn($sub) => $sub->where('kepemilikan', $val))
                      ->orWhereHas('klinikUtamaDetail', fn($sub) => $sub->where('kepemilikan', $val))
                      ->orWhereHas('laboratoriumDetail', fn($sub) => $sub->where('kepemilikan', $val));
                });
            }
        }

        // 2b. Tipe Rumah Sakit: A / B / C / D / D Pratama
        if ($request->filled('tipe_rs')) {
            $val = trim((string) $request->input('tipe_rs'));
            $allowed = ['A', 'B', 'C', 'D', 'D Pratama'];
            if (in_array($val, $allowed, true)) {
                $query->whereHas('rumahSakitDetail', function ($sub) use ($val) {
                    $sub->where('tipe_rs', $val);
                });
            }
        }

        // 2c. Kategori Puskesmas: Rawat Inap / Non Rawat Inap
        if ($request->filled('kategori_puskesmas')) {
            $val = trim((string) $request->input('kategori_puskesmas'));
            if ($val === 'rawat_inap' || $val === 'rawat_jalan') {
                $query->whereHas('puskesmasDetail', function ($sub) use ($val) {
                    $sub->where('kategori', $val);
                });
            }
        }

        // 2d. Wilayah Puskesmas: Perkotaan / Pedesaan
        if ($request->filled('wilayah_puskesmas')) {
            $val = trim((string) $request->input('wilayah_puskesmas'));
            if ($val === 'perkotaan' || $val === 'pedesaan') {
                $query->whereHas('puskesmasDetail', function ($sub) use ($val) {
                    $sub->where('wilayah', $val);
                });
            }
        }

        // 2e. Kemampuan Persalinan Puskesmas: PONED / NON PONED Mampu Salin / NON PONED Tidak Mampu Salin
        if ($request->filled('persalinan_puskesmas')) {
            $val = trim((string) $request->input('persalinan_puskesmas'));
            $query->whereHas('puskesmasDetail', function ($sub) use ($val) {
                if ($val === 'poned') {
                    $sub->where('poned', 'Ya PONED');
                } elseif ($val === 'mampu_salin') {
                    $sub->where(function ($s) {
                        $s->where('mampu_salin', 'Ya')
                          ->where(function ($p) {
                              $p->where('poned', '!=', 'Ya PONED')->orWhereNull('poned');
                          });
                    });
                } elseif ($val === 'tidak_mampu_salin') {
                    $sub->where(function ($s) {
                        $s->where('mampu_salin', 'Tidak')
                          ->where(function ($p) {
                              $p->where('poned', '!=', 'Ya PONED')->orWhereNull('poned');
                          });
                    });
                }
            });
        }

        // 2f. PONEK Rumah Sakit: PONEK / NON PONEK
        if ($request->filled('ponek_rs')) {
            $val = strtolower(trim((string) $request->input('ponek_rs')));
            $query->whereHas('rumahSakitDetail', function ($sub) use ($val) {
                if ($val === 'ponek' || $val === 'ya' || $val === '1') {
                    $sub->where(function ($s) {
                        $s->where('ponek', 'PONEK')->orWhere('ponek', 'Ya PONEK');
                    });
                } elseif ($val === 'non_ponek' || $val === 'tidak' || $val === '0') {
                    $sub->where(function ($s) {
                        $s->where('ponek', 'Tidak PONEK')
                          ->orWhere('ponek', 'NON PONEK')
                          ->orWhereNull('ponek');
                    });
                }
            });
        }

        // 2g. Kategori Layanan Klinik (Pratama & Utama): Rawat Jalan / Rawat Inap
        if ($request->filled('kategori_layanan_klinik')) {
            $val = trim((string) $request->input('kategori_layanan_klinik'));
            if ($val === 'rawat_jalan' || $val === 'rawat_inap') {
                $query->where(function ($q) use ($val) {
                    $q->whereHas('klinikPratamaDetail', fn($sub) => $sub->where('kategori_layanan', $val))
                      ->orWhereHas('klinikUtamaDetail', fn($sub) => $sub->where('kategori_layanan', $val));
                });
            }
        }

        // 2h. Kerja Sama BPJS Khusus Klinik (Pratama & Utama): Ya / Tidak
        if ($request->filled('bpjs_klinik')) {
            $val = trim((string) $request->input('bpjs_klinik'));
            $isBpjs = ($val === 'ya' || $val === '1' || $val === 'true');
            $query->where(function ($q) use ($isBpjs) {
                $q->whereHas('klinikPratamaDetail', fn($sub) => $sub->where('bpjs', $isBpjs))
                  ->orWhereHas('klinikUtamaDetail', fn($sub) => $sub->where('bpjs', $isBpjs));
            });
        }

        // 2i. Status Izin Operasional
        // Opsi: berlaku / berakhir_6_bulan / berakhir_3_bulan / berakhir_1_bulan / kedaluwarsa / belum_diisi
        if ($request->filled('status_izin')) {
            $val = trim((string) $request->input('status_izin'));
            $today = Carbon::today()->toDateString();
            $d30 = Carbon::today()->addDays(30)->toDateString();
            $d90 = Carbon::today()->addDays(90)->toDateString();
            $d180 = Carbon::today()->addDays(180)->toDateString();

            $detailTables = [
                'puskesmasDetail',
                'rumahSakitDetail',
                'klinikPratamaDetail',
                'klinikUtamaDetail',
                'laboratoriumDetail',
                'griyaSehatDetail',
            ];

            $query->where(function ($q) use ($val, $today, $d30, $d90, $d180, $detailTables) {
                foreach ($detailTables as $rel) {
                    $q->orWhereHas($rel, function ($sub) use ($val, $today, $d30, $d90, $d180) {
                        switch ($val) {
                            case 'berlaku':
                                $sub->whereNotNull('masa_izin')->where('masa_izin', '>', $d180);
                                break;
                            case 'berakhir_6_bulan':
                                $sub->whereNotNull('masa_izin')
                                    ->where('masa_izin', '>=', $today)
                                    ->where('masa_izin', '<=', $d180);
                                break;
                            case 'berakhir_3_bulan':
                                $sub->whereNotNull('masa_izin')
                                    ->where('masa_izin', '>=', $today)
                                    ->where('masa_izin', '<=', $d90);
                                break;
                            case 'berakhir_1_bulan':
                                $sub->whereNotNull('masa_izin')
                                    ->where('masa_izin', '>=', $today)
                                    ->where('masa_izin', '<=', $d30);
                                break;
                            case 'kedaluwarsa':
                                $sub->whereNotNull('masa_izin')->where('masa_izin', '<', $today);
                                break;
                            case 'belum_diisi':
                                $sub->whereNull('masa_izin');
                                break;
                        }
                    });
                }
            });
        }

        // 2j. Status Operasional Faskes: Aktif / Nonaktif / Semua
        if ($request->filled('status_operasional')) {
            $val = trim((string) $request->input('status_operasional'));
            if ($val === 'aktif' || $val === 'nonaktif') {
                $query->where('status', $val);
            }
            // jika 'semua', tidak membatasi status
        }

        // =========================================================================
        // 3. FILTER KOLOM TAMBAHAN DINAMIS (Custom Fields dari Tugas 2)
        // =========================================================================
        if ($request->filled('custom_filter') && is_array($request->input('custom_filter'))) {
            foreach ($request->input('custom_filter') as $fieldKey => $filterVal) {
                if ($filterVal !== null && $filterVal !== '') {
                    $cleanVal = trim((string) $filterVal);
                    $query->whereHas('fieldValues', function ($sub) use ($fieldKey, $cleanVal) {
                        $sub->where('value', $cleanVal)
                            ->whereHas('definition', function ($defSub) use ($fieldKey) {
                                $defSub->where('field_key', $fieldKey);
                            });
                    });
                }
            }
        }

        return $query;
    }
}
