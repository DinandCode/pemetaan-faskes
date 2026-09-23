<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Analisis Event - Faskes Terdekat</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 15px; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; color: #dc2626; }
        .subtitle { text-align: center; font-size: 10px; color: #64748b; margin-bottom: 12px; }
        .parameter-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 10px 14px; margin-bottom: 15px; font-size: 9px; }
        .parameter-box strong { color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background-color: #dc2626; color: #ffffff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9px; vertical-align: top; }
        tr:nth-child(even) { background-color: #fef2f2; }
        .best-route { background-color: #ecfdf5 !important; border-left: 3px solid #10b981; }
        .footer { text-align: center; font-size: 8px; color: #94a3b8; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; }
        .badge-rs { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-pkm { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-klinik { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-lab { background: #faf5ff; color: #7c3aed; border: 1px solid #e9d5ff; }
        .badge-upkdk { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .star { color: #d97706; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Laporan Analisis Lokasi Event - Rekomendasi Faskes & Rute</h1>
    <div class="subtitle">Dinas Kesehatan Kabupaten Banyumas &bull; Dicetak: {{ date('d F Y H:i') }} WIB</div>

    <div class="parameter-box">
        <strong>Parameter Analisis:</strong><br>
        Titik Koordinat Event: {{ $parameter['latitude'] ?? '-' }}, {{ $parameter['longitude'] ?? '-' }}
        &nbsp;&bull;&nbsp; Radius Maksimal: {{ $parameter['radius_km'] ?? '-' }} KM
        &nbsp;&bull;&nbsp; Filter Jenis: {{ ucwords(str_replace('_', ' ', $parameter['jenis_faskes'] ?? 'Semua')) }}
        &nbsp;&bull;&nbsp; Faskes Ditemukan: {{ count($data) }} Fasilitas
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:24%">Nama Faskes</th>
                <th style="width:14%">Jenis</th>
                <th style="width:15%">Kecamatan</th>
                <th style="width:17%">Alamat / Kontak</th>
                <th style="width:8%">Jarak Lurus</th>
                <th style="width:9%">Jarak Jalan</th>
                <th style="width:9%">Est. Waktu</th>
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
                        default => '',
                    };
                @endphp
                <tr class="{{ $index === 0 ? 'best-route' : '' }}">
                    <td>
                        {{ $index + 1 }}
                        @if($index === 0)
                            <span class="star">&#9733;</span>
                        @endif
                    </td>
                    <td>
                        <strong style="color: #0f172a;">{{ $faskes['nama'] ?? '-' }}</strong>
                        @if($index === 0)
                            <br><span style="color:#059669; font-size:8px; font-weight:bold;">(Rute Paling Efisien)</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $faskes['jenis_faskes'] ?? '-')) }}</span></td>
                    <td>{{ $faskes['kecamatan'] ?? '-' }}</td>
                    <td>
                        {{ $faskes['alamat'] ?: '-' }}
                        @if(!empty($faskes['nomor_telepon']))
                            <br><span style="color:#64748b; font-size:8px;">Telp: {{ $faskes['nomor_telepon'] }}</span>
                        @endif
                    </td>
                    <td>{{ number_format($item['jarak_lurus']['km'] ?? 0, 2) }} km</td>
                    <td>
                        <strong>{{ number_format($item['jarak_jalan']['km'] ?? 0, 2) }} km</strong>
                    </td>
                    <td>
                        <strong>{{ number_format($item['estimasi_waktu']['menit'] ?? 0, 1) }} menit</strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">
                        Tidak ada faskes ditemukan dalam radius ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} Sistem Informasi Geografis Faskes &bull; Dinas Kesehatan Kabupaten Banyumas
    </div>
</body>
</html>
