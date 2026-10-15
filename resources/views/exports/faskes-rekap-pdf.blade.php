<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Faskes - Kab. Banyumas</title>
    <style>
        @page {
            margin: 25px 25px 35px 25px;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        h1 {
            font-size: 14px;
            margin: 0 0 4px 0;
            color: #1e40af;
        }
        .subtitle {
            font-size: 8.5px;
            color: #64748b;
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
            background-color: #1e40af;
            color: #ffffff;
            padding: 5px 6px;
            text-align: left;
            font-size: 7.5px;
            text-transform: uppercase;
            border: 1px solid #1e3a8a;
        }
        td {
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            font-size: 7.5px;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .kecamatan-header {
            background-color: #e2e8f0;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 9px;
            color: #1e40af;
            margin-top: 10px;
            margin-bottom: 4px;
            border-left: 3px solid #1e40af;
        }
        .badge {
            display: inline-block;
            padding: 1.5px 4px;
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
        .badge-aktif { background: #d1fae5; color: #047857; }
        .badge-nonaktif { background: #f1f5f9; color: #475569; }
        .empty-message {
            text-align: center;
            padding: 20px;
            color: #64748b;
            font-style: italic;
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
    <div class="header">
        <h1>Rekapitulasi Fasilitas Kesehatan</h1>
        <div class="subtitle">Dinas Kesehatan Kabupaten Banyumas &bull; Dicetak: {{ $printedAt ?? now()->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i') }} WIB</div>
    </div>

    @if($faskesList->isEmpty())
        <div class="empty-message">
            Tidak ada data fasilitas kesehatan yang sesuai dengan kriteria filter.
        </div>
    @else
        @php
            $grouped = $faskesList->groupBy(fn ($item) => $item->kecamatan ?: 'Kecamatan Belum Terdata');
        @endphp

        @foreach($grouped as $kecamatan => $items)
            <div class="kecamatan-header">{{ $kecamatan }} ({{ $items->count() }} Faskes)</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:3%">No</th>
                        <th style="width:20%">Nama Faskes</th>
                        <th style="width:12%">Jenis Faskes</th>
                        <th style="width:23%">Alamat & Desa</th>
                        <th style="width:12%">Koordinat</th>
                        <th style="width:14%">Layanan / Kapasitas</th>
                        <th style="width:10%">Kontak</th>
                        <th style="width:6%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $i => $faskes)
                        @php
                            $badgeClass = match($faskes->jenis_faskes) {
                                'rumah_sakit' => 'badge-rs',
                                'puskesmas' => 'badge-pkm',
                                'klinik_pratama', 'klinik_utama' => 'badge-klinik',
                                'laboratorium' => 'badge-lab',
                                'upkdk' => 'badge-upkdk',
                                'griya_sehat' => 'badge-griya',
                                'tpmd', 'tpmdg', 'tpmb', 'tpmp' => 'badge-tpm',
                                default => '',
                            };

                            $layanan = '-';
                            if ($faskes->jenis_faskes === 'puskesmas' && $faskes->puskesmasDetail) {
                                $layanan = \App\Support\KategoriPelayanan::label($faskes->puskesmasDetail->kategori ?? '');
                                if ($faskes->puskesmasDetail->jumlah_tempat_tidur > 0) {
                                    $layanan .= " (TT: {$faskes->puskesmasDetail->jumlah_tempat_tidur})";
                                }
                            } elseif ($faskes->jenis_faskes === 'rumah_sakit' && $faskes->rumahSakitDetail) {
                                $layanan = ($faskes->rumahSakitDetail->tipe_rs ? 'Tipe ' . $faskes->rumahSakitDetail->tipe_rs . ' ' : '') . ($faskes->rumahSakitDetail->kemampuan_pelayanan ?: 'RS Umum');
                            } elseif (($faskes->jenis_faskes === 'klinik_pratama' || $faskes->jenis_faskes === 'klinik_utama') && $faskes->detail) {
                                $layanan = ($faskes->detail->kategori_layanan ? \App\Support\KategoriPelayanan::label($faskes->detail->kategori_layanan) : 'Klinik');
                                if (!empty($faskes->detail->bed_rawat_inap) && $faskes->detail->bed_rawat_inap > 0) {
                                    $layanan .= " (TT: {$faskes->detail->bed_rawat_inap})";
                                }
                            }
                        @endphp
                        <tr>
                            <td style="text-align: center;">{{ $i + 1 }}</td>
                            <td><strong style="color: #0f172a;">{{ $faskes->nama }}</strong></td>
                            <td><span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $faskes->jenis_faskes)) }}</span></td>
                            <td>
                                {{ $faskes->alamat ?: '-' }}
                                @if(!empty($faskes->desa))
                                    <br><span style="color:#64748b; font-size:7px;">Desa {{ $faskes->desa }}</span>
                                @endif
                            </td>
                            <td style="font-family: monospace; font-size:7px;">
                                @if($faskes->latitude && $faskes->longitude)
                                    {{ number_format($faskes->latitude, 6) }},<br>{{ number_format($faskes->longitude, 6) }}
                                @else
                                    <span style="color:#94a3b8;">-</span>
                                @endif
                            </td>
                            <td>{{ $layanan }}</td>
                            <td>{{ $faskes->nomor_telepon ?: '-' }}</td>
                            <td style="text-align: center;">
                                <span class="badge {{ $faskes->status === 'aktif' ? 'badge-aktif' : 'badge-nonaktif' }}">{{ ucfirst($faskes->status) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <div class="footer">
        Halaman <span class="pagenum"></span> &bull; Sistem Informasi Geografis Pemetaan Faskes Banyumas &bull; &copy; {{ date('Y') }}
    </div>
</body>
</html>
