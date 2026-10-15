<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Analisis Event - Faskes Terdekat</title>
    <style>
        @page {
            margin: 25px 25px 35px 25px;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        h1 {
            font-size: 14px;
            text-align: center;
            margin: 0 0 4px 0;
            color: #dc2626;
        }
        .subtitle {
            text-align: center;
            font-size: 8px;
            color: #64748b;
            margin-bottom: 10px;
        }
        .parameter-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 12px;
            font-size: 8px;
        }
        .parameter-box strong {
            color: #991b1b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
        }
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th {
            background-color: #dc2626;
            color: #ffffff;
            padding: 5px 6px;
            text-align: left;
            font-size: 7.5px;
            text-transform: uppercase;
            border: 1px solid #b91c1c;
        }
        td {
            padding: 5px 6px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #fef2f2;
        }
        .best-route {
            background-color: #ecfdf5 !important;
            border-left: 3px solid #10b981;
        }
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
        }
        .badge-rs { background: #fee2e2; color: #b91c1c; }
        .badge-pkm { background: #dbeafe; color: #1d4ed8; }
        .badge-klinik { background: #d1fae5; color: #047857; }
        .badge-lab { background: #ede9fe; color: #6d28d9; }
        .badge-upkdk { background: #fef3c7; color: #b45309; }
        .badge-griya { background: #cffafe; color: #0e7490; }
        .badge-tpm { background: #dcfce7; color: #15803d; }
        .star {
            color: #d97706;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 15px;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
        }
        .pagenum:before {
            content: counter(page);
        }
    </style>
</head>
<body>
    <h1>Laporan Analisis Lokasi Event - Rekomendasi Faskes & Rute</h1>
    <div class="subtitle">Dinas Kesehatan Kabupaten Banyumas &bull; Dicetak: {{ $printedAt ?? now()->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i') }} WIB</div>

    <div class="parameter-box">
        <strong>Parameter Analisis:</strong><br>
        Titik Koordinat Event: {{ $parameter['latitude'] ?? '-' }}, {{ $parameter['longitude'] ?? '-' }}
        &nbsp;&bull;&nbsp; Radius Maksimal: {{ $parameter['radius_km'] ?? '-' }} KM
        &nbsp;&bull;&nbsp; Filter Jenis: {{ ucwords(str_replace('_', ' ', is_array($parameter['jenis_faskes'] ?? null) ? implode(', ', $parameter['jenis_faskes']) : ($parameter['jenis_faskes'] ?? 'Semua'))) }}
        &nbsp;&bull;&nbsp; Faskes Terpilih: {{ count($data) }} Fasilitas
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:6%">Rank</th>
                <th style="width:12%">Prioritas</th>
                <th style="width:20%">Nama Faskes</th>
                <th style="width:11%">Jenis</th>
                <th style="width:12%">Kecamatan</th>
                <th style="width:17%">Alamat / Kontak</th>
                <th style="width:6%">Jarak Lurus</th>
                <th style="width:6%">Jarak Jalan</th>
                <th style="width:6%">Est. Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                @php
                    $faskes = $item['detail_faskes'] ?? [];
                    $badgeClass = match($faskes['jenis_faskes'] ?? '') {
                        'rumah_sakit' => 'badge-rs',
                        'puskesmas' => 'badge-pkm',
                        'klinik_pratama', 'klinik_utama' => 'badge-klinik',
                        'laboratorium' => 'badge-lab',
                        'upkdk' => 'badge-upkdk',
                        'griya_sehat' => 'badge-griya',
                        'tpmd', 'tpmdg', 'tpmb', 'tpmp' => 'badge-tpm',
                        default => '',
                    };
                    $rank = $item['rekomendasi_rank'] ?? ($index + 1);
                    $tierLabel = $item['prioritas_label'] ?? (isset($item['prioritas_tier']) ? "Tier {$item['prioritas_tier']}" : '-');
                @endphp
                <tr class="{{ $index === 0 ? 'best-route' : '' }}">
                    <td style="text-align: center;">
                        {{ $index + 1 }}
                        @if($index === 0)
                            <span class="star">&#9733;</span>
                        @endif
                    </td>
                    <td style="text-align: center; font-weight: bold;">
                        #{{ $rank }}
                    </td>
                    <td>
                        <span style="font-weight: bold; font-size: 7.5px;">{{ $tierLabel }}</span>
                    </td>
                    <td>
                        <strong style="color: #0f172a;">{{ $faskes['nama'] ?? '-' }}</strong>
                        @if($index === 0 && ($item['rute_tersedia'] ?? false))
                            <br><span style="color:#059669; font-size:7px; font-weight:bold;">(REKOMENDASI UTAMA)</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $faskes['jenis_faskes'] ?? '-')) }}</span></td>
                    <td>{{ $faskes['kecamatan'] ?? '-' }}</td>
                    <td>
                        {{ $faskes['alamat'] ?: '-' }}
                        @if(!empty($faskes['nomor_telepon']))
                            <br><span style="color:#64748b; font-size:7px;">Telp: {{ $faskes['nomor_telepon'] }}</span>
                        @endif
                    </td>
                    <td style="text-align: right;">{{ number_format($item['jarak_lurus']['km'] ?? 0, 2) }} km</td>
                    <td style="text-align: right;">
                        @if($item['rute_tersedia'] ?? false)
                            <strong>{{ number_format($item['jarak_jalan']['km'] ?? 0, 2) }} km</strong>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        @if($item['rute_tersedia'] ?? false)
                            <strong>{{ number_format($item['estimasi_waktu']['menit'] ?? 0, 1) }} mnt</strong>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">
                        Tidak ada faskes ditemukan dalam radius dan kriteria ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Halaman <span class="pagenum"></span> &bull; Laporan Analisis Respons Cepat &bull; Dinas Kesehatan Kabupaten Banyumas &bull; &copy; {{ date('Y') }}
    </div>
</body>
</html>
