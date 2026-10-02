<?php

namespace App\Services;

use App\Models\Faskes;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IzinOperasionalService
{
    public const TIMEZONE = 'Asia/Jakarta';

    public const THRESHOLD_H1_MONTHS = 1;
    public const THRESHOLD_H3_MONTHS = 3;
    public const THRESHOLD_H6_MONTHS = 6;

    public const MONITORED_TYPES = [
        'puskesmas'      => 'Puskesmas',
        'rumah_sakit'    => 'Rumah Sakit',
        'klinik_pratama' => 'Klinik Pratama',
        'klinik_utama'   => 'Klinik Utama',
        'laboratorium'   => 'Laboratorium',
        'griya_sehat'    => 'Griya Sehat',
    ];

    /**
     * Mengambil data dasar seluruh faskes aktif yang memiliki kolom masa_izin.
     * Menggunakan satu query efisien dengan LEFT JOIN ke keenam tabel detail.
     */
    public function getBaseData(?string $jenisFaskes = null, ?string $kecamatan = null): Collection
    {
        $today = Carbon::now(self::TIMEZONE)->startOfDay();
        $h1Limit = $today->copy()->addMonthsNoOverflow(self::THRESHOLD_H1_MONTHS);
        $h3Limit = $today->copy()->addMonthsNoOverflow(self::THRESHOLD_H3_MONTHS);
        $h6Limit = $today->copy()->addMonthsNoOverflow(self::THRESHOLD_H6_MONTHS);

        $monitoredKeys = array_keys(self::MONITORED_TYPES);

        $query = DB::table('faskes as f')
            ->leftJoin('puskesmas_details as p', function ($j) {
                $j->on('p.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'puskesmas');
            })
            ->leftJoin('rumah_sakit_details as rs', function ($j) {
                $j->on('rs.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'rumah_sakit');
            })
            ->leftJoin('klinik_pratama_details as kp', function ($j) {
                $j->on('kp.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'klinik_pratama');
            })
            ->leftJoin('klinik_utama_details as ku', function ($j) {
                $j->on('ku.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'klinik_utama');
            })
            ->leftJoin('laboratorium_details as lab', function ($j) {
                $j->on('lab.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'laboratorium');
            })
            ->leftJoin('griya_sehat_details as gs', function ($j) {
                $j->on('gs.faskes_id', '=', 'f.id')->where('f.jenis_faskes', '=', 'griya_sehat');
            })
            ->whereRaw('LOWER(f.status) = ?', ['aktif'])
            ->whereIn('f.jenis_faskes', $monitoredKeys)
            ->select([
                'f.id',
                'f.nama',
                'f.jenis_faskes',
                'f.kecamatan',
                'f.desa',
                'f.nomor_telepon',
                DB::raw('COALESCE(p.masa_izin, rs.masa_izin, kp.masa_izin, ku.masa_izin, lab.masa_izin, gs.masa_izin) as masa_izin'),
            ]);

        if (! empty($jenisFaskes) && in_array($jenisFaskes, $monitoredKeys, true)) {
            $query->where('f.jenis_faskes', $jenisFaskes);
        }

        if (! empty($kecamatan)) {
            $query->whereRaw('LOWER(f.kecamatan) = ?', [mb_strtolower(trim($kecamatan))]);
        }

        $records = $query->get();

        return $records->map(function ($row) use ($today, $h1Limit, $h3Limit, $h6Limit) {
            $jenisLabel = self::MONITORED_TYPES[$row->jenis_faskes] ?? ucfirst(str_replace('_', ' ', $row->jenis_faskes));
            $editUrl = route('faskes.edit', $row->id);

            if (empty($row->masa_izin)) {
                return [
                    'id'                  => (int) $row->id,
                    'nama'                => $row->nama,
                    'jenis_faskes'        => $row->jenis_faskes,
                    'jenis_label'         => $jenisLabel,
                    'kecamatan'           => $row->kecamatan,
                    'desa'                => $row->desa,
                    'nomor_telepon'       => $row->nomor_telepon,
                    'masa_izin'           => null,
                    'masa_izin_formatted' => '-',
                    'sisa_hari'           => null,
                    'sisa_waktu_text'     => 'Belum diisi',
                    'level'               => 'belum_diisi',
                    'level_label'         => 'Belum Diisi',
                    'badge_class'         => 'bg-slate-100 text-slate-600 border border-slate-200',
                    'edit_url'            => $editUrl,
                ];
            }

            $date = Carbon::parse($row->masa_izin, self::TIMEZONE)->startOfDay();
            $sisaHari = (int) $today->diffInDays($date, false);

            if ($sisaHari < 0) {
                $level = 'kedaluwarsa';
                $levelLabel = 'Kedaluwarsa';
                $sisaWaktuText = 'Lewat ' . abs($sisaHari) . ' hari';
                $badgeClass = 'bg-rose-950 text-rose-100 border border-rose-800 font-bold';
            } elseif ($date->lte($h1Limit)) {
                $level = 'h_1_bulan';
                $levelLabel = 'H-1 Bulan';
                $sisaWaktuText = $sisaHari === 0 ? 'Berakhir hari ini' : 'Tinggal ' . $sisaHari . ' hari';
                $badgeClass = 'bg-rose-100 text-rose-800 border border-rose-300 font-bold';
            } elseif ($date->lte($h3Limit)) {
                $level = 'h_3_bulan';
                $levelLabel = 'H-3 Bulan';
                $sisaWaktuText = 'Tinggal ' . $sisaHari . ' hari';
                $badgeClass = 'bg-orange-100 text-orange-800 border border-orange-300 font-bold';
            } elseif ($date->lte($h6Limit)) {
                $level = 'h_6_bulan';
                $levelLabel = 'H-6 Bulan';
                $sisaWaktuText = 'Tinggal ' . $sisaHari . ' hari';
                $badgeClass = 'bg-amber-100 text-amber-800 border border-amber-300 font-bold';
            } else {
                $level = 'aman';
                $levelLabel = 'Aman (> 6 Bulan)';
                $sisaWaktuText = 'Tinggal ' . $sisaHari . ' hari';
                $badgeClass = 'bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium';
            }

            return [
                'id'                  => (int) $row->id,
                'nama'                => $row->nama,
                'jenis_faskes'        => $row->jenis_faskes,
                'jenis_label'         => $jenisLabel,
                'kecamatan'           => $row->kecamatan,
                'desa'                => $row->desa,
                'nomor_telepon'       => $row->nomor_telepon,
                'masa_izin'           => $date->format('Y-m-d'),
                'masa_izin_formatted' => $date->translatedFormat('d M Y'),
                'sisa_hari'           => $sisaHari,
                'sisa_waktu_text'     => $sisaWaktuText,
                'level'               => $level,
                'level_label'         => $levelLabel,
                'badge_class'         => $badgeClass,
                'edit_url'            => $editUrl,
            ];
        });
    }

    /**
     * Menghitung ringkasan jumlah faskes per level peringatan izin operasional.
     */
    public function getRingkasan(?string $jenisFaskes = null, ?string $kecamatan = null): array
    {
        $data = $this->getBaseData($jenisFaskes, $kecamatan);

        $kedaluwarsa = $data->where('level', 'kedaluwarsa')->count();
        $h1 = $data->where('level', 'h_1_bulan')->count();
        $h3 = $data->where('level', 'h_3_bulan')->count();
        $h6 = $data->where('level', 'h_6_bulan')->count();
        $belumDiisi = $data->where('level', 'belum_diisi')->count();
        $aman = $data->where('level', 'aman')->count();

        return [
            'total_pantau' => $data->count(),
            'kedaluwarsa'  => $kedaluwarsa,
            'h_1_bulan'    => $h1,
            'h_3_bulan'    => $h3,
            'h_6_bulan'    => $h6,
            'belum_diisi'  => $belumDiisi,
            'aman'         => $aman,
            'total_urgent' => $kedaluwarsa + $h1,
        ];
    }

    /**
     * Mengambil daftar faskes paling mendesak untuk popup notifikasi lonceng (maksimal $limit).
     */
    public function getUrgentList(int $limit = 10): array
    {
        $data = $this->getBaseData();

        // Ambil faskes urgent (kedaluwarsa dan h-1 bulan)
        $urgent = $data->filter(function ($item) {
            return in_array($item['level'], ['kedaluwarsa', 'h_1_bulan'], true);
        })->sortBy('sisa_hari')->take($limit)->values();

        // Jika faskes urgent kurang dari limit, tambahkan dari h-3 bulan jika ada
        if ($urgent->count() < $limit) {
            $remaining = $limit - $urgent->count();
            $h3 = $data->where('level', 'h_3_bulan')->sortBy('sisa_hari')->take($remaining)->values();
            $urgent = $urgent->concat($h3);
        }

        return $urgent->toArray();
    }

    /**
     * Mengembalikan data terpaginasi lengkap dengan filter level, jenis faskes, dan kecamatan.
     */
    public function getPagedData(array $filters = []): array
    {
        $jenisFaskes = $filters['jenis_faskes'] ?? null;
        $kecamatan = $filters['kecamatan'] ?? null;
        $level = $filters['level'] ?? 'all';

        $data = $this->getBaseData($jenisFaskes, $kecamatan);

        // Filter berdasarkan level
        if ($level === 'urgent') {
            $filtered = $data->filter(fn($item) => in_array($item['level'], ['kedaluwarsa', 'h_1_bulan'], true));
        } elseif ($level !== 'all' && in_array($level, ['kedaluwarsa', 'h_1_bulan', 'h_3_bulan', 'h_6_bulan', 'belum_diisi', 'aman'], true)) {
            $filtered = $data->where('level', $level);
        } else {
            $filtered = $data;
        }

        // Urutkan dari sisa hari paling sedikit (null di akhir)
        $sorted = $filtered->sort(function ($a, $b) {
            if ($a['sisa_hari'] === null && $b['sisa_hari'] === null) {
                return strcmp($a['nama'], $b['nama']);
            }
            if ($a['sisa_hari'] === null) {
                return 1;
            }
            if ($b['sisa_hari'] === null) {
                return -1;
            }
            return $a['sisa_hari'] <=> $b['sisa_hari'];
        })->values();

        // Pagination
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(50, max(5, (int) ($filters['per_page'] ?? 10)));
        $total = $sorted->count();
        $lastPage = (int) max(1, ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $items = $sorted->slice($offset, $perPage)->values();

        return [
            'ringkasan'  => $this->getRingkasan($jenisFaskes, $kecamatan),
            'data'       => $items->toArray(),
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => $lastPage,
                'from'         => $total > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $total),
            ],
        ];
    }
}
