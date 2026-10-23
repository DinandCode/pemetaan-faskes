<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Kualitas Data Faskes - Kab. Banyumas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-sans min-h-screen flex flex-col">

    <!-- Header Navbar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-black text-lg shadow-sm">
                    DQ
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-900 leading-tight tracking-tight">Audit Kualitas Data Faskes (Data Quality)</h1>
                    <p class="text-[11px] font-medium text-slate-500">Dinas Kesehatan Kab. Banyumas &bull; Status: Baca-Saja (Read-Only)</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 transition">
                    Dashboard
                </a>
                <a href="{{ url('/peta') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 transition">
                    Peta GIS
                </a>
                <a href="{{ route('faskes.index') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 transition">
                    Master Faskes
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 space-y-6">

        <!-- Banner Info Rekonsiliasi -->
        <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-5 text-white shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-500/30 text-blue-200 text-xs font-semibold mb-2">
                        <span>Ringkasan Master Data</span>
                    </div>
                    <h2 class="text-lg font-bold">Rekonsiliasi Angka: 913 Master vs 904 Peta</h2>
                    <p class="text-xs text-blue-200/90 mt-1 max-w-2xl leading-relaxed">
                        Total faskes di database berjumlah <b>{{ $totalMaster }}</b>. Peta GIS secara sengaja hanya menampilkan <b>{{ $totalAktif }}</b> faskes yang berstatus <code>aktif</code> dan berkoordinat valid. Terdapat <b>{{ $totalNonaktif }}</b> faskes nonaktif yang tidak ditampilkan, dan <b>1</b> faskes aktif yang koordinatnya berada di luar batas Banyumas.
                    </p>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center flex-shrink-0">
                    <div class="bg-white/10 backdrop-blur-xs rounded-xl p-3 border border-white/15">
                        <div class="text-xl font-extrabold">{{ $totalMaster }}</div>
                        <div class="text-[10px] text-blue-200 mt-0.5">Total Master</div>
                    </div>
                    <div class="bg-emerald-500/20 backdrop-blur-xs rounded-xl p-3 border border-emerald-400/30 text-emerald-300">
                        <div class="text-xl font-extrabold">{{ $totalAktif }}</div>
                        <div class="text-[10px] text-emerald-200 mt-0.5">Aktif (Peta)</div>
                    </div>
                    <div class="bg-rose-500/20 backdrop-blur-xs rounded-xl p-3 border border-rose-400/30 text-rose-300">
                        <div class="text-xl font-extrabold">{{ $totalNonaktif }}</div>
                        <div class="text-[10px] text-rose-200 mt-0.5">Nonaktif</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1: Daftar 9 Faskes Nonaktif -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-slate-800">1. Daftar Faskes Nonaktif (9 Faskes Selisih Peta)</h3>
                    <p class="text-xs text-slate-500">Faskes ini tersimpan di database master namun tidak dimuat pada peta utama.</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                    {{ $faskesNonaktif->count() }} Faskes
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-4">ID</th>
                            <th class="py-2.5 px-4">Nama Faskes</th>
                            <th class="py-2.5 px-4">Jenis</th>
                            <th class="py-2.5 px-4">Kecamatan</th>
                            <th class="py-2.5 px-4">Desa</th>
                            <th class="py-2.5 px-4">Koordinat</th>
                            <th class="py-2.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($faskesNonaktif as $f)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-2.5 px-4 font-mono text-slate-500">#{{ $f->id }}</td>
                                <td class="py-2.5 px-4 font-semibold text-slate-800">{{ $f->nama }}</td>
                                <td class="py-2.5 px-4"><span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">{{ strtoupper($f->jenis_faskes) }}</span></td>
                                <td class="py-2.5 px-4 text-slate-600">{{ $f->kecamatan ?? '-' }}</td>
                                <td class="py-2.5 px-4 text-slate-500">{{ $f->desa ?? '-' }}</td>
                                <td class="py-2.5 px-4 font-mono text-slate-500 text-[11px]">{{ $f->latitude }}, {{ $f->longitude }}</td>
                                <td class="py-2.5 px-4 text-right">
                                    <a href="{{ route('faskes.edit', $f->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-xs">Edit &rarr;</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Koordinat di Luar Bounding Box Banyumas -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-slate-800">2. Koordinat di Luar Wilayah Banyumas</h3>
                    <p class="text-xs text-slate-500">Koordinat berada di luar kotak pembatas Kab. Banyumas (-7.75 s/d -7.15, 108.95 s/d 109.50).</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $luarBanyumas->count() > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                    {{ $luarBanyumas->count() }} Temuan
                </span>
            </div>
            @if($luarBanyumas->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-amber-50/50 text-slate-600 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">ID</th>
                                <th class="py-2.5 px-4">Nama Faskes</th>
                                <th class="py-2.5 px-4">Jenis</th>
                                <th class="py-2.5 px-4">Kecamatan</th>
                                <th class="py-2.5 px-4">Koordinat Saat Ini</th>
                                <th class="py-2.5 px-4">Keterangan</th>
                                <th class="py-2.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($luarBanyumas as $f)
                                <tr class="hover:bg-amber-50/30 transition">
                                    <td class="py-2.5 px-4 font-mono text-slate-500">#{{ $f->id }}</td>
                                    <td class="py-2.5 px-4 font-semibold text-slate-800">{{ $f->nama }}</td>
                                    <td class="py-2.5 px-4"><span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">{{ strtoupper($f->jenis_faskes) }}</span></td>
                                    <td class="py-2.5 px-4 text-slate-600">{{ $f->kecamatan ?? '-' }}</td>
                                    <td class="py-2.5 px-4 font-mono text-amber-700 font-semibold text-[11px]">{{ $f->latitude }}, {{ $f->longitude }}</td>
                                    <td class="py-2.5 px-4 text-slate-600 text-[11px]">Longitude 108.9100628 berada di sebelah barat batas Lumbir</td>
                                    <td class="py-2.5 px-4 text-right">
                                        <a href="{{ route('faskes.edit', $f->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-xs">Koreksi Titik &rarr;</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center text-xs text-slate-500">Semua faskes aktif berada dalam rentang koordinat Banyumas.</div>
            @endif
        </div>

        <!-- Section 3: Variasi Penulisan Kecamatan Anomali -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-slate-800">3. Variasi Penulisan Nama Kecamatan Tidak Baku</h3>
                    <p class="text-xs text-slate-500">Ditemukan faskes dengan awalan 'Kec.', huruf kecil, trailing comma, atau nama desa di kolom kecamatan.</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">
                    {{ $kecamatanAnomali->count() }} Faskes
                </span>
            </div>
            <div class="max-h-72 overflow-y-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200 sticky top-0">
                        <tr>
                            <th class="py-2.5 px-4">ID</th>
                            <th class="py-2.5 px-4">Nama Faskes</th>
                            <th class="py-2.5 px-4">Kecamatan Tercatat</th>
                            <th class="py-2.5 px-4">Desa</th>
                            <th class="py-2.5 px-4">Kategori Masalah</th>
                            <th class="py-2.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($kecamatanAnomali as $f)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-2 px-4 font-mono text-slate-500">#{{ $f->id }}</td>
                                <td class="py-2 px-4 font-semibold text-slate-800">{{ $f->nama }}</td>
                                <td class="py-2 px-4 font-mono text-rose-700 bg-rose-50/50 px-2 py-0.5 rounded">{{ $f->kecamatan ?? '[NULL]' }}</td>
                                <td class="py-2 px-4 text-slate-500">{{ $f->desa ?? '-' }}</td>
                                <td class="py-2 px-4 text-[11px] text-slate-500">
                                    @if(is_null($f->kecamatan) || trim($f->kecamatan) === '')
                                        <span class="text-rose-600 font-bold">Kecamatan Kosong/Null</span>
                                    @elseif(str_starts_with($f->kecamatan, 'Kec.'))
                                        <span class="text-amber-700 font-medium">Awalan 'Kec.'</span>
                                    @elseif(in_array($f->kecamatan, ['Ajibarang Wetan', 'Kelurahan Karangklesem', 'Kedungwringin']))
                                        <span class="text-blue-700 font-medium">Desa tertukar ke Kecamatan</span>
                                    @elseif(str_ends_with($f->kecamatan, ','))
                                        <span class="text-amber-700 font-medium">Tanda koma trailing (',')</span>
                                    @else
                                        <span class="text-purple-700 font-medium">Case typo (huruf kecil/kapital)</span>
                                    @endif
                                </td>
                                <td class="py-2 px-4 text-right">
                                    <a href="{{ route('faskes.edit', $f->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-xs">Ubah &rarr;</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 4: Duplikasi Data & Anomali Izin -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Duplikasi Data -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-slate-800">4. Duplikasi Data Faskes</h3>
                        <p class="text-xs text-slate-500">Nama, jenis, dan kecamatan identik persis.</p>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-800">
                        {{ $duplikatData->count() }} Pasangan
                    </span>
                </div>
                <div class="p-4 space-y-3">
                    @foreach($duplikatData as $dup)
                        <div class="p-3 rounded-xl border border-rose-200 bg-rose-50/30 text-xs">
                            <div class="font-bold text-slate-800">{{ $dup->nama }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Jenis: {{ strtoupper($dup->jenis_faskes) }} &bull; Kec: {{ $dup->kecamatan }}</div>
                            <div class="mt-2 text-[11px] font-mono text-rose-700 font-semibold">
                                ID Faskes Kembar: {{ implode(', ', (array) json_decode($dup->ids ?? '[]')) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Izin Operasional & Lintas Kolom -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-slate-800">5. Anomali Izin & Lintas Kolom</h3>
                        <p class="text-xs text-slate-500">Tanggal izin lampau ekstrem & inkonsistensi bed.</p>
                    </div>
                </div>
                <div class="p-4 space-y-3 text-xs">
                    <!-- Tanggal Ekstrem -->
                    @foreach($izinLampauEkstrem as $iz)
                        <div class="p-3 rounded-xl border border-amber-200 bg-amber-50/30">
                            <div class="font-bold text-slate-800">{{ $iz->nama }} (#{{ $iz->id }})</div>
                            <div class="text-amber-800 mt-0.5">Masa Izin Tercatat: <b class="font-mono">{{ $iz->masa_izin }}</b> (Lewat > 25 tahun, terduga salah input tahun).</div>
                        </div>
                    @endforeach

                    <!-- Inkonsistensi Puskesmas -->
                    @foreach($inkonsistensiPuskesmas as $pkm)
                        <div class="p-3 rounded-xl border border-slate-200 bg-slate-50">
                            <div class="font-bold text-slate-800">{{ $pkm->nama }} (#{{ $pkm->id }})</div>
                            <div class="text-slate-600 mt-0.5">Kategori: <b>{{ $pkm->kategori }}</b> tetapi Tempat Tidur: <b>{{ $pkm->jumlah_tempat_tidur }}</b>.</div>
                        </div>
                    @endforeach

                    <!-- Inkonsistensi Klinik -->
                    @foreach($inkonsistensiKlinik as $kln)
                        <div class="p-3 rounded-xl border border-slate-200 bg-slate-50">
                            <div class="font-bold text-slate-800">{{ $kln->nama }} (#{{ $kln->id }})</div>
                            <div class="text-slate-600 mt-0.5">Kategori: <b>{{ $kln->kategori_layanan }}</b> tetapi Tempat Tidur: <b>0</b>.</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-400 mt-8">
        Sistem Informasi Geografis Pemetaan Fasilitas Kesehatan &bull; Dinas Kesehatan Kabupaten Banyumas
    </footer>

</body>
</html>
