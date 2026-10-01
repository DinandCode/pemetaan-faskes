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
    <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm shadow-blue-500/30 flex-shrink-0">
                    <!-- Icon: health / cross -->
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h16a1 1 0 001-1V8.5a1 1 0 00-.4-.8l-7-5.25a1 1 0 00-1.2 0l-7 5.25a1 1 0 00-.4.8V20a1 1 0 001 1z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-900 leading-tight tracking-tight">Master Data Fasilitas Kesehatan</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Dinas Kesehatan Kabupaten Banyumas</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16v-4m5 4V8m5 8v-6" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ url('/peta') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75L3 9.253v11.25L9 18m0-11.25l6 2.25m-6-2.25v11.25m6-9l6-2.25v11.25L15 18m0-11.25v11.25m0 0l-6 2.25" />
                    </svg>
                    <span>Peta GIS</span>
                </a>

                <div class="w-px h-6 bg-slate-200 mx-0.5 hidden sm:block"></div>

                <!-- Import Excel Button -->
                <button type="button" @click="importModalOpen = true"
                        class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                    </svg>
                    <span>Import Excel</span>
                </button>

                <!-- Export Excel Button -->
                <a href="{{ route('faskes.export.excel', request()->query()) }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center gap-1.5 shadow-sm shadow-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-8 4h10a2 2 0 002-2V6a2 2 0 00-2-2H9a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Export Excel</span>
                </a>

                <!-- Export PDF Button -->
                <a href="{{ route('faskes.export.pdf', request()->query()) }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition flex items-center gap-1.5 shadow-sm shadow-rose-500/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14v5a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v1M9 13h1.5a1.5 1.5 0 010 3H9v-3zm0 3v2m5-5v5m0-5h1.25a1.25 1.25 0 010 2.5H14m0 0V19" />
                    </svg>
                    <span>Export PDF</span>
                </a>

                <a href="{{ route('faskes.create') }}"
                   class="px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-sm shadow-blue-500/25 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tambah Faskes</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 space-y-4">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-0.5 rounded transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M4.062 19.5h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4.5c-.77-1.333-2.694-1.333-3.464 0L2.33 16.5c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 p-0.5 rounded transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs">
                <div class="flex items-center gap-2 font-bold mb-1.5">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <span>Terjadi kesalahan validasi:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 pl-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter & Search Card -->
        <div class="bg-white rounded-xl p-4 border border-slate-200">
            <form action="{{ route('faskes.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search Input -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cari Nama / Alamat</label>
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari faskes, kecamatan, desa..."
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                </div>

                <!-- Filter Jenis Faskes -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Jenis Faskes</label>
                    <select name="jenis_faskes" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <option value="">Semua Jenis</option>
                        <option value="rumah_sakit" {{ request('jenis_faskes') == 'rumah_sakit' ? 'selected' : '' }}>Rumah Sakit</option>
                        <option value="puskesmas" {{ request('jenis_faskes') == 'puskesmas' ? 'selected' : '' }}>Puskesmas</option>
                        <option value="klinik_pratama" {{ request('jenis_faskes') == 'klinik_pratama' ? 'selected' : '' }}>Klinik Pratama</option>
                        <option value="klinik_utama" {{ request('jenis_faskes') == 'klinik_utama' ? 'selected' : '' }}>Klinik Utama</option>
                        <option value="laboratorium" {{ request('jenis_faskes') == 'laboratorium' ? 'selected' : '' }}>Laboratorium</option>
                        <option value="upkdk" {{ request('jenis_faskes') == 'upkdk' ? 'selected' : '' }}>UPKDK (Pustu / PKD)</option>
                        <option value="griya_sehat" {{ request('jenis_faskes') == 'griya_sehat' ? 'selected' : '' }}>Griya Sehat</option>
                        <option value="tpmd" {{ request('jenis_faskes') == 'tpmd' ? 'selected' : '' }}>TPMD (Praktik Mandiri Dokter)</option>
                        <option value="tpmdg" {{ request('jenis_faskes') == 'tpmdg' ? 'selected' : '' }}>TPMDG (Praktik Mandiri Dokter Gigi)</option>
                        <option value="tpmb" {{ request('jenis_faskes') == 'tpmb' ? 'selected' : '' }}>TPMB (Praktik Mandiri Bidan)</option>
                        <option value="tpmp" {{ request('jenis_faskes') == 'tpmp' ? 'selected' : '' }}>TPMP (Praktik Mandiri Perawat)</option>
                    </select>
                </div>

                <!-- Filter Status -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="flex-1 py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs transition flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('faskes.index') }}"
                       class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-lg text-xs transition flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Reset</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Table Data Card -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wide font-semibold text-[10.5px]">
                        <tr>
                            <th class="py-3 px-4">Nama Faskes</th>
                            <th class="py-3 px-4">Jenis</th>
                            <th class="py-3 px-4">Wilayah / Alamat</th>
                            <th class="py-3 px-4">Koordinat (Lat, Lng)</th>
                            <th class="py-3 px-4">Spesifikasi Detail</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($faskesList as $faskes)
                            <tr class="hover:bg-slate-50/70 transition">
                                <!-- Nama & Kontak -->
                                <td class="py-3 px-4 font-semibold text-slate-800 align-top">
                                    <div class="font-bold text-slate-900 text-xs">{{ $faskes->nama }}</div>
                                    @if($faskes->nomor_telepon)
                                        <div class="text-[11px] text-slate-500 font-normal mt-0.5 flex items-center gap-1">
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                            </svg>
                                            <span>{{ $faskes->nomor_telepon }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Jenis Badge -->
                                <td class="py-3 px-4 align-top">
                                    @php
                                        $badgeClasses = [
                                            'rumah_sakit'    => 'bg-red-50 text-red-700 border-red-200',
                                            'puskesmas'      => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'klinik_pratama' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'klinik_utama'   => 'bg-teal-50 text-teal-700 border-teal-200',
                                            'laboratorium'   => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'upkdk'          => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'griya_sehat'    => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                            'tpmd'           => 'bg-sky-50 text-sky-700 border-sky-200',
                                            'tpmdg'          => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'tpmb'           => 'bg-pink-50 text-pink-700 border-pink-200',
                                            'tpmp'           => 'bg-lime-50 text-lime-700 border-lime-200',
                                        ];
                                        $labels = [
                                            'rumah_sakit'    => 'Rumah Sakit',
                                            'puskesmas'      => 'Puskesmas',
                                            'klinik_pratama' => 'Klinik Pratama',
                                            'klinik_utama'   => 'Klinik Utama',
                                            'laboratorium'   => 'Laboratorium',
                                            'upkdk'          => 'UPKDK',
                                            'griya_sehat'    => 'Griya Sehat',
                                            'tpmd'           => 'TPMD',
                                            'tpmdg'          => 'TPMDG',
                                            'tpmb'           => 'TPMB',
                                            'tpmp'           => 'TPMP',
                                        ];
                                        $badgeClass = $badgeClasses[$faskes->jenis_faskes] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        $label = $labels[$faskes->jenis_faskes] ?? $faskes->jenis_faskes;
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                        {{ $label }}
                                    </span>
                                </td>

                                <!-- Alamat & Kecamatan -->
                                <td class="py-3 px-4 text-slate-600 align-top">
                                    <div>{{ $faskes->alamat ?: '-' }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        Kec. {{ $faskes->kecamatan ?: '-' }}, Desa {{ $faskes->desa ?: '-' }}
                                    </div>
                                </td>

                                <!-- Koordinat -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600 align-top">
                                    <div>{{ number_format($faskes->latitude, 6) }}</div>
                                    <div>{{ number_format($faskes->longitude, 6) }}</div>
                                </td>

                                <!-- Spesifikasi Detail -->
                                <td class="py-3 px-4 text-[11px] align-top">
                                    @php
                                        $icAmbulans = '<svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.83H14.25M16.5 18.75h-2.25m0-11.25h-8.25a1.125 1.125 0 00-1.125 1.125v8.25c0 .621.504 1.125 1.125 1.125h1.5m5.25-10.5V18.75m0-11.25H12" /></svg>';
                                    @endphp

                                    @if($faskes->jenis_faskes === 'puskesmas' && $faskes->puskesmasDetail)
                                        @php
                                            $pkm = $faskes->puskesmasDetail;
                                            $ambulansPkm = (int) $pkm->ambulans_transport + (int) $pkm->ambulans_roda_dua;
                                        @endphp
                                        <div class="flex flex-wrap gap-1 mb-1">
                                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-600 font-medium">{{ ucfirst(str_replace('_', ' ', $pkm->kategori)) }}</span>
                                            @if($pkm->poned === 'Ya PONED')
                                                <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded font-medium">PONED</span>
                                            @elseif($pkm->mampu_salin === 'Ya')
                                                <span class="px-1.5 py-0.5 bg-sky-100 text-sky-700 rounded font-medium">Mampu Salin</span>
                                            @endif
                                            @if($pkm->jumlah_tempat_tidur > 0)
                                                <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 18v-8.25A2.25 2.25 0 016 7.5h12a2.25 2.25 0 012.25 2.25V18M3.75 18h16.5M3.75 18v1.5M20.25 18v1.5M6.75 7.5V6a1.5 1.5 0 011.5-1.5h7.5A1.5 1.5 0 0117.25 6v1.5" /></svg>
                                                    {{ $pkm->jumlah_tempat_tidur }} Bed
                                                </span>
                                            @endif
                                            <span class="px-1.5 py-0.5 rounded font-medium inline-flex items-center gap-1 {{ $ambulansPkm > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-400' }}">
                                                {!! $icAmbulans !!}
                                                {{ $ambulansPkm }} Ambulans
                                            </span>
                                        </div>
                                        <div class="text-slate-500 flex flex-wrap gap-x-2 gap-y-0.5 text-[10.5px]">
                                            <span>SDM: {{ (int) $pkm->jumlah_sdm }}</span>
                                            @if($pkm->wilayah)
                                                <span>&bull; {{ ucfirst(str_replace('_', ' ', $pkm->wilayah)) }}</span>
                                            @endif
                                        </div>

                                    @elseif($faskes->jenis_faskes === 'rumah_sakit' && $faskes->rumahSakitDetail)
                                        @php
                                            $rs = $faskes->rumahSakitDetail;
                                            $ambulansRs = (int) $rs->ambulans_transport + (int) $rs->ambulans_gadar;
                                        @endphp
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                @if($rs->tipe_rs)
                                                    <span class="px-1.5 py-0.5 bg-red-100 text-red-800 font-bold rounded text-[10px]">Tipe {{ $rs->tipe_rs }}</span>
                                                @endif
                                                <span class="font-medium text-slate-700">{{ $rs->kemampuan_pelayanan ?: 'Umum' }}</span>
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                @if($rs->ponek === 'Ya PONEK')
                                                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded text-[10px] font-medium">PONEK</span>
                                                @endif
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium inline-flex items-center gap-1 {{ $ambulansRs > 0 ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-400' }}">
                                                    {!! $icAmbulans !!}
                                                    {{ $ambulansRs }} Ambulans
                                                </span>
                                            </div>
                                        </div>

                                    @elseif($faskes->jenis_faskes === 'klinik_pratama' && $faskes->klinikPratamaDetail)
                                        @php $kp = $faskes->klinikPratamaDetail; @endphp
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap gap-1">
                                                @if($kp->kategori_layanan)
                                                    <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-600 font-medium">{{ ucfirst(str_replace('_', ' ', $kp->kategori_layanan)) }}</span>
                                                @endif
                                                @if($kp->kepemilikan)
                                                    <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-600 font-medium">{{ $kp->kepemilikan }}</span>
                                                @endif
                                                @if($kp->bpjs)
                                                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded font-medium">BPJS</span>
                                                @endif
                                                @if($kp->bed_rawat_inap > 0)
                                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium">{{ $kp->bed_rawat_inap }} Bed</span>
                                                @endif
                                                <span class="px-1.5 py-0.5 rounded font-medium inline-flex items-center gap-1 {{ $kp->ambulans_transport > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-400' }}">
                                                    {!! $icAmbulans !!}
                                                    {{ (int) $kp->ambulans_transport }} Ambulans
                                                </span>
                                            </div>
                                            <div class="text-slate-500 truncate max-w-[160px]">{{ $kp->jenis_layanan ?: '-' }}</div>
                                            <div class="text-slate-400 text-[10.5px]">SDM: {{ (int) $kp->jumlah_sdm }}</div>
                                        </div>

                                    @elseif($faskes->jenis_faskes === 'klinik_utama' && $faskes->klinikUtamaDetail)
                                        @php $ku = $faskes->klinikUtamaDetail; @endphp
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap gap-1 items-center">
                                                @if($ku->kategori_layanan)
                                                    <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-600 font-medium">{{ ucfirst(str_replace('_', ' ', $ku->kategori_layanan)) }}</span>
                                                @endif
                                                <span class="font-medium text-slate-700">{{ $ku->kepemilikan ?: 'Swasta' }}</span>
                                                @if($ku->bpjs)
                                                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded font-medium">BPJS</span>
                                                @endif
                                                @if($ku->bed_rawat_inap > 0)
                                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium">{{ $ku->bed_rawat_inap }} Bed</span>
                                                @endif
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium inline-flex items-center gap-1 {{ $ku->ambulans > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-400' }}">
                                                    {!! $icAmbulans !!}
                                                    {{ (int) $ku->ambulans }} Ambulans
                                                </span>
                                            </div>
                                            <div class="text-slate-500 truncate max-w-[160px]">{{ $ku->kemampuan_layanan ?: '-' }}</div>
                                        </div>

                                    @elseif($faskes->jenis_faskes === 'laboratorium' && $faskes->laboratoriumDetail)
                                        @php $lab = $faskes->laboratoriumDetail; @endphp
                                        <div class="space-y-0.5">
                                            <div class="text-slate-500 truncate max-w-[160px]">{{ $lab->jenis_layanan ?: 'Lab Umum' }}</div>
                                            @if($lab->kepemilikan)
                                                <div class="text-slate-400 text-[10.5px]">{{ $lab->kepemilikan }}</div>
                                            @endif
                                        </div>

                                    @elseif($faskes->jenis_faskes === 'upkdk' && $faskes->upkdkDetail)
                                        @php $upk = $faskes->upkdkDetail; @endphp
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded font-semibold uppercase">{{ $upk->jenis ?? 'UPKDK' }}</span>
                                            @if($upk->is_pustu === 'Ya')
                                                <span class="px-1.5 py-0.5 bg-orange-100 text-orange-700 rounded font-medium">Pustu</span>
                                            @endif
                                            @if($upk->is_pkd === 'Ya')
                                                <span class="px-1.5 py-0.5 bg-teal-100 text-teal-700 rounded font-medium">PKD</span>
                                            @endif
                                            <span class="text-slate-500">SDM: {{ (int) $upk->jumlah_sdm }}</span>
                                        </div>

                                    @elseif($faskes->jenis_faskes === 'griya_sehat' && $faskes->griyaSehatDetail)
                                        @php $gs = $faskes->griyaSehatDetail; @endphp
                                        <div class="space-y-0.5">
                                            <div class="font-medium text-slate-700">Griya Sehat</div>
                                            <div class="text-slate-500 text-[10.5px]">
                                                @if($gs->pj) PJ: {{ $gs->pj }} @endif
                                                @if($gs->jumlah_sdm) &bull; SDM: {{ (int) $gs->jumlah_sdm }} @endif
                                            </div>
                                        </div>

                                    @elseif(in_array($faskes->jenis_faskes, ['tpmd', 'tpmdg', 'tpmb', 'tpmp']))
                                        <div class="text-slate-500 italic text-[10.5px]">Praktik Mandiri</div>

                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4 align-top">
                                    @if($faskes->status === 'aktif')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-4 text-center align-top">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('faskes.edit', $faskes->id) }}"
                                           class="p-1.5 bg-slate-100 hover:bg-blue-50 text-slate-500 hover:text-blue-600 rounded-lg transition"
                                           title="Edit Faskes">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5V19.5a2.25 2.25 0 01-2.25 2.25H4.5A2.25 2.25 0 012.25 19.5V6.75A2.25 2.25 0 014.5 4.5h6" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('faskes.destroy', $faskes->id) }}" method="POST"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus data faskes ini? Data detail juga akan dihapus.');"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-1.5 bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 rounded-lg transition"
                                                    title="Hapus Faskes">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5M6 9.75V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v3.75" />
                                    </svg>
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
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Import Data Rekapitulasi Dinkes</h3>
                        <p class="text-[11px] text-slate-500">Format Excel (.xlsx, .xls, .csv)</p>
                    </div>
                </div>
                <button @click="importModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-md transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('faskes.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-xl p-5 text-center transition bg-slate-50">
                    <svg class="w-6 h-6 mx-auto mb-2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-2">Maksimal ukuran file: 10 MB</p>
                </div>

                <!-- Info Mapping Kolom -->
                <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-3 text-[11px] text-slate-600 space-y-1">
                    <p class="font-bold text-blue-900 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        Kolom Sheet Rekapitulasi yang Didukung:
                    </p>
                    <p class="text-slate-500">
                        <code>nama / nama_faskes</code>, <code>jenis_faskes</code> (puskesmas, rumah_sakit, klinik_pratama, dll),
                        <code>latitude</code>, <code>longitude</code>, <code>alamat</code>, <code>kecamatan</code>, <code>desa</code>, <code>nomor_telepon</code>.
                    </p>
                    <p class="text-slate-400 text-[10px] mt-1">
                        *Kolom koordinat akan otomatis dipetakan ke field spasial PostGIS <code>lokasi</code> (SRID 4326) dan data spesifikasi disimpan ke tabel child yang sesuai.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="importModalOpen = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm shadow-blue-500/25">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
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