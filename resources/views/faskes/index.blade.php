<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data Fasilitas Kesehatan - Kab. Banyumas</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans" x-data="{ importModalOpen: false }">

    <!-- Header Navigation -->
    <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-500/20">
                    🏥
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-800 leading-tight">Master Data Fasilitas Kesehatan</h1>
                    <p class="text-xs text-slate-500">Dinas Kesehatan Kabupaten Banyumas</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center space-x-1.5">
                    <span>📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="{{ url('/peta') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center space-x-1.5">
                    <span>🗺️</span>
                    <span>Peta GIS</span>
                </a>

                <!-- Import Excel Button -->
                <button type="button" @click="importModalOpen = true"
                        class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 transition flex items-center space-x-1.5 shadow-sm">
                    <span>📥</span>
                    <span>Import Excel</span>
                </button>

                <!-- Export Excel Button -->
                <a href="{{ route('faskes.export.excel', request()->query()) }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-green-600 hover:bg-green-700 text-white transition flex items-center space-x-1.5 shadow-sm">
                    <span>📊</span>
                    <span>Export Excel</span>
                </a>

                <!-- Export PDF Button -->
                <a href="{{ route('faskes.export.pdf', request()->query()) }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition flex items-center space-x-1.5 shadow-sm">
                    <span>📄</span>
                    <span>Export PDF</span>
                </a>

                <a href="{{ route('faskes.create') }}"
                   class="px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 transition flex items-center space-x-1.5">
                    <span>➕</span>
                    <span>Tambah Faskes</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 space-y-4">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="text-base">✅</span>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="text-base">⚠️</span>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 font-bold">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs shadow-sm">
                <div class="font-bold mb-1">Terjadi kesalahan validasi:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter & Search Card -->
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
            <form action="{{ route('faskes.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search Input -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Cari Nama / Alamat</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari faskes, kecamatan, desa..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Filter Jenis Faskes -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Faskes</label>
                    <select name="jenis_faskes" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Jenis</option>
                        <option value="rumah_sakit" {{ request('jenis_faskes') == 'rumah_sakit' ? 'selected' : '' }}>Rumah Sakit</option>
                        <option value="puskesmas" {{ request('jenis_faskes') == 'puskesmas' ? 'selected' : '' }}>Puskesmas</option>
                        <option value="klinik_pratama" {{ request('jenis_faskes') == 'klinik_pratama' ? 'selected' : '' }}>Klinik Pratama</option>
                        <option value="klinik_utama" {{ request('jenis_faskes') == 'klinik_utama' ? 'selected' : '' }}>Klinik Utama</option>
                        <option value="laboratorium" {{ request('jenis_faskes') == 'laboratorium' ? 'selected' : '' }}>Laboratorium</option>
                        <option value="upkdk" {{ request('jenis_faskes') == 'upkdk' ? 'selected' : '' }}>UPKDK (Pustu / PKD)</option>
                    </select>
                </div>

                <!-- Filter Status -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end space-x-2">
                    <button type="submit"
                            class="flex-1 py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs transition">
                        Filter
                    </button>
                    <a href="{{ route('faskes.index') }}"
                       class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-lg text-xs transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Table Data Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-3.5 px-4">Nama Faskes</th>
                            <th class="py-3.5 px-4">Jenis</th>
                            <th class="py-3.5 px-4">Wilayah / Alamat</th>
                            <th class="py-3.5 px-4">Koordinat (Lat, Lng)</th>
                            <th class="py-3.5 px-4">Spesifikasi Detail</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($faskesList as $faskes)
                            <tr class="hover:bg-slate-50/60 transition">
                                <!-- Nama & Kontak -->
                                <td class="py-3 px-4 font-semibold text-slate-800">
                                    <div class="font-bold text-slate-900 text-xs">{{ $faskes->nama }}</div>
                                    @if($faskes->nomor_telepon)
                                        <div class="text-[11px] text-slate-500 font-normal">📞 {{ $faskes->nomor_telepon }}</div>
                                    @endif
                                </td>

                                <!-- Jenis Badge -->
                                <td class="py-3 px-4">
                                    @php
                                        $badgeClasses = [
                                            'rumah_sakit'    => 'bg-red-50 text-red-700 border-red-200',
                                            'puskesmas'      => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'klinik_pratama' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'klinik_utama'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'laboratorium'   => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'upkdk'          => 'bg-amber-50 text-amber-800 border-amber-200',
                                        ];
                                        $labels = [
                                            'rumah_sakit'    => 'Rumah Sakit',
                                            'puskesmas'      => 'Puskesmas',
                                            'klinik_pratama' => 'Klinik Pratama',
                                            'klinik_utama'   => 'Klinik Utama',
                                            'laboratorium'   => 'Laboratorium',
                                            'upkdk'          => 'UPKDK',
                                        ];
                                        $badgeClass = $badgeClasses[$faskes->jenis_faskes] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        $label = $labels[$faskes->jenis_faskes] ?? $faskes->jenis_faskes;
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                        {{ $label }}
                                    </span>
                                </td>

                                <!-- Alamat & Kecamatan -->
                                <td class="py-3 px-4 text-slate-600">
                                    <div>{{ $faskes->alamat ?: '-' }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        Kec. {{ $faskes->kecamatan ?: '-' }}, Desa {{ $faskes->desa ?: '-' }}
                                    </div>
                                </td>

                                <!-- Koordinat -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    <div>{{ number_format($faskes->latitude, 6) }}</div>
                                    <div>{{ number_format($faskes->longitude, 6) }}</div>
                                </td>

                                <!-- Spesifikasi Detail -->
                                <td class="py-3 px-4 text-[11px]">
                                    @if($faskes->jenis_faskes === 'puskesmas' && $faskes->puskesmasDetail)
                                        <div class="flex flex-wrap gap-1">
                                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-600 font-medium">{{ ucfirst(str_replace('_', ' ', $faskes->puskesmasDetail->kategori)) }}</span>
                                            @if($faskes->puskesmasDetail->poned) <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded font-medium">PONED</span> @endif
                                            @if($faskes->puskesmasDetail->jumlah_tempat_tidur > 0) <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium">{{ $faskes->puskesmasDetail->jumlah_tempat_tidur }} Bed</span> @endif
                                            @if($faskes->puskesmasDetail->ambulans_transport) <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded font-medium">🚑 Amb</span> @endif
                                        </div>
                                    @elseif($faskes->jenis_faskes === 'rumah_sakit' && $faskes->rumahSakitDetail)
                                        <div class="space-y-0.5">
                                            <div class="font-medium text-slate-700">{{ $faskes->rumahSakitDetail->kemampuan_pelayanan ?: 'Umum' }}</div>
                                            @if($faskes->rumahSakitDetail->ambulans_gadar) <span class="px-1.5 py-0.5 bg-red-100 text-red-700 rounded text-[10px] font-medium">🚑 Gadar</span> @endif
                                        </div>
                                    @elseif($faskes->jenis_faskes === 'klinik_pratama' && $faskes->klinikPratamaDetail)
                                        <div class="space-y-0.5">
                                            @if($faskes->klinikPratamaDetail->bpjs) <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded font-medium">BPJS</span> @endif
                                            @if($faskes->klinikPratamaDetail->bed_rawat_inap > 0) <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium">{{ $faskes->klinikPratamaDetail->bed_rawat_inap }} Bed</span> @endif
                                            <div class="text-slate-500 truncate max-w-[160px]">{{ $faskes->klinikPratamaDetail->jenis_layanan }}</div>
                                        </div>
                                    @elseif($faskes->jenis_faskes === 'klinik_utama' && $faskes->klinikUtamaDetail)
                                        <div class="space-y-0.5">
                                            <span class="font-medium text-slate-700">{{ $faskes->klinikUtamaDetail->kepemilikan ?: 'Swasta' }}</span>
                                            <div class="text-slate-500 truncate max-w-[160px]">{{ $faskes->klinikUtamaDetail->kemampuan_layanan }}</div>
                                        </div>
                                    @elseif($faskes->jenis_faskes === 'laboratorium' && $faskes->laboratoriumDetail)
                                        <div class="text-slate-500 truncate max-w-[160px]">{{ $faskes->laboratoriumDetail->jenis_layanan ?: 'Lab Umum' }}</div>
                                    @elseif($faskes->jenis_faskes === 'upkdk' && $faskes->upkdkDetail)
                                        <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded font-semibold uppercase">{{ $faskes->upkdkDetail->jenis }}</span>
                                        <span class="text-slate-500">SDM: {{ $faskes->upkdkDetail->jumlah_sdm }}</span>
                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    @if($faskes->status === 'aktif')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                            ● Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                                            ○ Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <a href="{{ route('faskes.edit', $faskes->id) }}"
                                           class="p-1.5 bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 rounded-lg transition"
                                           title="Edit Faskes">
                                            ✏️
                                        </a>
                                        <form action="{{ route('faskes.destroy', $faskes->id) }}" method="POST"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus data faskes ini? Data detail juga akan dihapus.');"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-1.5 bg-slate-100 hover:bg-red-50 text-slate-600 hover:text-red-600 rounded-lg transition"
                                                    title="Hapus Faskes">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    <span class="text-3xl block mb-2">📂</span>
                                    <span class="text-xs">Tidak ada data faskes yang sesuai dengan filter pencarian.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if($faskesList->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $faskesList->links() }}
                </div>
            @endif
        </div>

    </main>

    <!-- Modal Import Excel -->
    <div x-show="importModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="importModalOpen = false"
             class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 border border-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-2xl">📥</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Import Data Rekapitulasi Dinkes</h3>
                        <p class="text-[11px] text-slate-500">Format Excel (.xlsx, .xls, .csv)</p>
                    </div>
                </div>
                <button @click="importModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
            </div>

            <form action="{{ route('faskes.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-xl p-5 text-center transition bg-slate-50">
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-2">Maksimal ukuran file: 10 MB</p>
                </div>

                <!-- Info Mapping Kolom -->
                <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-3 text-[11px] text-slate-600 space-y-1">
                    <p class="font-bold text-blue-900">Kolom Sheet Rekapitulasi yang Didukung:</p>
                    <p class="text-slate-500">
                        <code>nama / nama_faskes</code>, <code>jenis_faskes</code> (puskesmas, rumah_sakit, klinik_pratama, dll),
                        <code>latitude</code>, <code>longitude</code>, <code>alamat</code>, <code>kecamatan</code>, <code>desa</code>, <code>nomor_telepon</code>.
                    </p>
                    <p class="text-slate-400 text-[10px] mt-1">
                        *Kolom koordinat akan otomatis dipetakan ke field spasial PostGIS <code>lokasi</code> (SRID 4326) dan data spesifikasi disimpan ke tabel child yang sesuai.
                    </p>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="importModalOpen = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs transition flex items-center space-x-1 shadow-md shadow-blue-500/20">
                        <span>Unggah & Impor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-3 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} Sistem Informasi Geografis Faskes &bull; Dinas Kesehatan Kabupaten Banyumas
    </footer>

</body>
</html>
