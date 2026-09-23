<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Faskes - Kab. Banyumas</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 15px; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; color: #1e40af; }
        .subtitle { text-align: center; font-size: 10px; color: #64748b; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background-color: #1e40af; color: #ffffff; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9px; vertical-align: top; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .footer { text-align: center; font-size: 8px; color: #94a3b8; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; }
        .badge-rs { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-pkm { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-klinik { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-lab { background: #faf5ff; color: #7c3aed; border: 1px solid #e9d5ff; }
        .badge-upkdk { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-aktif { background: #ecfdf5; color: #059669; }
        .badge-nonaktif { background: #f1f5f9; color: #64748b; }
        .kecamatan-header { background-color: #f1f5f9; padding: 6px 10px; font-weight: bold; font-size: 11px; color: #1e40af; margin-top: 12px; margin-bottom: 4px; border-left: 4px solid #1e40af; }
    </style>
</head>
<body>
    <h1>Rekapitulasi Fasilitas Kesehatan</h1>
    <div class="subtitle">Dinas Kesehatan Kabupaten Banyumas &bull; Dicetak: {{ date('d F Y H:i') }} WIB</div>

    @php
        $grouped = $faskesList->groupBy('kecamatan');
    @endphp

    @foreach($grouped as $kecamatan => $items)
        <div class="kecamatan-header">Kecamatan {{ $kecamatan ?: 'Tidak Diketahui' }} ({{ $items->count() }} Faskes)</div>
        <table>
            <thead>
                <tr>
                    <th style="width:4%">No</th>
                    <th style="width:24%">Nama Faskes</th>
                    <th style="width:14%">Jenis</th>
                    <th style="width:26%">Alamat / Desa</th>
                    <th style="width:14%">Koordinat</th>
                    <th style="width:11%">Telepon</th>
                    <th style="width:7%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $i => $faskes)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-weight:bold; color: #0f172a;">{{ $faskes->nama }}</td>
                        <td>
                            @php
                                $badgeClass = match($faskes->jenis_faskes) {
                                    'rumah_sakit' => 'badge-rs',
                                    'puskesmas' => 'badge-pkm',
                                    'klinik_pratama', 'klinik_utama' => 'badge-klinik',
                                    'laboratorium' => 'badge-lab',
                                    'upkdk' => 'badge-upkdk',
                                    default => '',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $faskes->jenis_faskes)) }}</span>
                        </td>
                        <td>{{ $faskes->alamat ?: '-' }}<br><span style="color:#64748b; font-size:8px;">Desa {{ $faskes->desa ?: '-' }}</span></td>
                        <td style="font-size:8px; font-family: monospace;">{{ number_format($faskes->latitude, 6) }},<br>{{ number_format($faskes->longitude, 6) }}</td>
                        <td>{{ $faskes->nomor_telepon ?: '-' }}</td>
                        <td>
                            <span class="badge {{ $faskes->status === 'aktif' ? 'badge-aktif' : 'badge-nonaktif' }}">{{ ucfirst($faskes->status) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        &copy; {{ date('Y') }} Sistem Informasi Geografis Faskes &bull; Dinas Kesehatan Kabupaten Banyumas
    </div>
</body>
</html>
