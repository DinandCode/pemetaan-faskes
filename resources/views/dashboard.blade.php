<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analitik Fasilitas Kesehatan - Kab. Banyumas</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Font Family Setup -->
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

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50/80 text-slate-800 antialiased min-h-screen flex flex-col font-sans">

    <!-- Header Navigation -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 px-6 py-3.5 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-900 leading-tight tracking-tight">Dashboard Analitik Faskes</h1>
                    <p class="text-[11px] font-medium text-slate-500">Dinas Kesehatan Kabupaten Banyumas &bull; Monitoring Pelayanan & Perizinan</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Notifikasi Lonceng Izin Operasional (Tugas 6) -->
                <div class="relative" x-data="{ notifOpen: false }">
                    <button type="button"
                            @click="notifOpen = !notifOpen"
                            class="relative p-2 rounded-lg bg-white hover:bg-slate-100 text-slate-600 border border-slate-200/80 transition shadow-2xs flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-rose-400"
                            title="Peringatan Masa Izin Operasional">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if(($ringkasanIzin['total_urgent'] ?? 0) > 0)
                            <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-600 text-white text-[9px] font-extrabold shadow-sm animate-pulse">
                                {{ $ringkasanIzin['total_urgent'] }}
                            </span>
                        @endif
                    </button>

                    <!-- Dropdown Panel Notifikasi -->
                    <div x-show="notifOpen"
                         @click.away="notifOpen = false"
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 overflow-hidden">
                        
                        <div class="px-4 py-3 bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200/80 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ ($ringkasanIzin['total_urgent'] ?? 0) > 0 ? 'bg-rose-500 animate-ping' : 'bg-emerald-500' }}"></span>
                                <h4 class="text-xs font-bold text-slate-800">Peringatan Masa Izin</h4>
                            </div>
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full {{ ($ringkasanIzin['total_urgent'] ?? 0) > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ ($ringkasanIzin['total_urgent'] ?? 0) > 0 ? $ringkasanIzin['total_urgent'] . ' Mendesak' : 'Semua Aman' }}
                            </span>
                        </div>

                        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                            @forelse($urgentIzinList as $item)
                                <a href="{{ $item['edit_url'] }}"
                                   class="block p-3 hover:bg-slate-50 transition group">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-slate-900 truncate group-hover:text-blue-600 transition-colors">
                                                {{ $item['nama'] }}
                                            </p>
                                            <p class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                                <span>{{ $item['jenis_label'] }}</span>
                                                <span>&bull;</span>
                                                <span>Kec. {{ $item['kecamatan'] ?: '-' }}</span>
                                            </p>
                                        </div>
                                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold flex-shrink-0 {{ $item['badge_class'] }}">
                                            {{ $item['level_label'] }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center justify-between text-[10px]">
                                        <span class="font-mono text-slate-500">Izin: {{ $item['masa_izin_formatted'] }}</span>
                                        <span class="font-bold {{ $item['level'] === 'kedaluwarsa' ? 'text-rose-700' : 'text-amber-700' }}">
                                            {{ $item['sisa_waktu_text'] }}
                                        </span>
                                    </div>
                                </a>
                            @empty
                                <div class="p-6 text-center text-slate-400">
                                    <svg class="w-8 h-8 mx-auto text-emerald-400 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-xs font-bold text-slate-700">Semua izin masih aman</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Tidak ada faskes dengan masa izin kedaluwarsa atau H-1 bulan.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="p-2.5 bg-slate-50 border-t border-slate-100 text-center">
                            <a href="#section-izin-operasional"
                               @click="notifOpen = false"
                               class="text-xs font-bold text-blue-600 hover:text-blue-700 block py-1">
                                Lihat Semua Pemantauan Izin &darr;
                            </a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('dashboard') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 border border-blue-200/60 flex items-center space-x-2 transition-all">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ url('/peta') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-white hover:bg-slate-100 text-slate-600 border border-slate-200/80 transition-all flex items-center space-x-2 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <span>Peta GIS</span>
                </a>
                <a href="{{ route('faskes.index') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-white hover:bg-slate-100 text-slate-600 border border-slate-200/80 transition-all flex items-center space-x-2 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>Master Faskes</span>
                </a>
                <a href="{{ route('faskes.create') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-sm shadow-blue-500/20 hover:shadow-md transition-all flex items-center space-x-2">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Faskes</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 space-y-6">

        <!-- 1. WIDGET RINGKASAN (CARDS) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

            <!-- Card 1: Total Faskes -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center space-x-3.5 hover:border-blue-300 hover:shadow-md transition-all group">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V7m0 0h4m-4 0H9"/>
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Faskes</span>
                    <span class="text-2xl font-extrabold text-slate-900 leading-none tracking-tight">{{ $totalFaskes }}</span>
                    <span class="text-[10px] font-medium text-slate-400 block mt-1">Kabupaten Banyumas</span>
                </div>
            </div>

            <!-- Card 2: Total Puskesmas -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center space-x-3.5 hover:border-sky-300 hover:shadow-md transition-all group">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center flex-shrink-0 group-hover:bg-sky-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Puskesmas</span>
                    <span class="text-2xl font-extrabold text-sky-600 leading-none tracking-tight">{{ $totalPuskesmas }}</span>
                    <span class="text-[10px] font-medium text-slate-400 block mt-1">Pusat Kesehatan Masy.</span>
                </div>
            </div>

            <!-- Card 3: Total Rumah Sakit -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center space-x-3.5 hover:border-rose-300 hover:shadow-md transition-all group">
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0 group-hover:bg-rose-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Rumah Sakit</span>
                    <span class="text-2xl font-extrabold text-rose-600 leading-none tracking-tight">{{ $totalRumahSakit }}</span>
                    <span class="text-[10px] font-medium text-slate-400 block mt-1">RSUD & Rujukan</span>
                </div>
            </div>

            <!-- Card 4: Total Bed Rawat Inap -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center space-x-3.5 hover:border-emerald-300 hover:shadow-md transition-all group">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Bed Rawat Inap</span>
                    <span class="text-2xl font-extrabold text-emerald-600 leading-none tracking-tight">{{ $totalBed }}</span>
                    <span class="text-[10px] font-medium text-slate-400 block mt-1">Kapasitas Tempat Tidur</span>
                </div>
            </div>

            <!-- Card 5: Total Ambulans Aktif -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center space-x-3.5 hover:border-amber-300 hover:shadow-md transition-all group">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Ambulans Aktif</span>
                    <span class="text-2xl font-extrabold text-amber-600 leading-none tracking-tight">{{ $totalAmbulans }}</span>
                    <span class="text-[10px] font-medium text-slate-400 block mt-1">Faskes Siaga Rujukan</span>
                </div>
            </div>

        </div>

        <!-- 2. GRAFIK STATISTIK (CHART.JS) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Grafik 1: Faskes per Kecamatan (Bar Chart) - 2 Kolom -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 tracking-tight">Jumlah Faskes per Kecamatan</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Sebaran fasilitas kesehatan di wilayah Kabupaten Banyumas</p>
                    </div>
                    <span class="px-2.5 py-1 bg-slate-100/80 text-slate-600 rounded-full text-xs font-semibold border border-slate-200/50">
                        {{ count($chartKecamatanLabels) }} Kecamatan
                    </span>
                </div>
                <div class="relative flex-1 min-h-[280px]">
                    <canvas id="chartKecamatan"></canvas>
                </div>
            </div>

            <!-- Grafik 2: Persentase Kategori Faskes (Donut Chart) - 1 Kolom -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 tracking-tight">Persentase Kategori Faskes</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Proporsi jenis faskes terdaftar</p>
                    </div>
                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-semibold border border-blue-100">
                        {{ $totalFaskes }} Total
                    </span>
                </div>
                <div class="relative flex-1 min-h-[280px] flex items-center justify-center">
                    <canvas id="chartKategori"></canvas>
                </div>
            </div>

        </div>

        <!-- 3. TABEL RINGKASAN: 5 FASKES DENGAN IZIN MENDEKATI KADALUARSA -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 tracking-tight">Monitoring Masa Berlaku Izin Operasional Faskes</h2>
                        <p class="text-xs text-slate-500 mt-0.5">5 fasilitas kesehatan dengan tanggal izin terdekat untuk tindak lanjut perpanjangan</p>
                    </div>
                </div>
                <a href="{{ route('faskes.index') }}" class="text-xs text-blue-600 hover:text-blue-700 font-bold flex items-center space-x-1 group self-start sm:self-auto">
                    <span>Lihat Semua Faskes</span>
                    <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 uppercase tracking-wider font-bold">
                        <tr>
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Fasilitas Kesehatan</th>
                            <th class="py-3.5 px-4">Jenis Faskes</th>
                            <th class="py-3.5 px-4">Kecamatan / Wilayah</th>
                            <th class="py-3.5 px-4">Tanggal Masa Izin</th>
                            <th class="py-3.5 px-4">Status & Sisa Waktu</th>
                            <th class="py-3.5 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($allWithIzin as $idx =>$item)
                            @php
                                $faskes =$item['faskes'];
                                $badgeClasses = [
                                    'rumah_sakit'    => 'bg-rose-50 text-rose-700 border-rose-200/60',
                                    'puskesmas'      => 'bg-blue-50 text-blue-700 border-blue-200/60',
                                    'klinik_pratama' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                                    'klinik_utama'   => 'bg-teal-50 text-teal-700 border-teal-200/60',
                                    'laboratorium'   => 'bg-purple-50 text-purple-700 border-purple-200/60',
                                    'upkdk'          => 'bg-amber-50 text-amber-800 border-amber-200/60',
                                ];$labels = [
                                    'rumah_sakit'    => 'Rumah Sakit',
                                    'puskesmas'      => 'Puskesmas',
                                    'klinik_pratama' => 'Klinik Pratama',
                                    'klinik_utama'   => 'Klinik Utama',
                                    'laboratorium'   => 'Laboratorium',
                                    'upkdk'          => 'UPKDK',
                                ];

                                $statusPillClass = match($item['status_badge']) {
                                    'expired'  => 'bg-red-50 text-red-700 border-red-200 font-semibold',
                                    'critical' => 'bg-rose-100/80 text-rose-800 border-rose-200 font-bold animate-pulse',
                                    'warning'  => 'bg-amber-50 text-amber-800 border-amber-200 font-semibold',
                                    default    => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-semibold',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 px-4 text-center font-bold text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 text-sm leading-snug">{{ $faskes->nama }}</div>
                                    @if($faskes->nomor_telepon)
                                        <div class="text-[11px] text-slate-400 font-medium flex items-center space-x-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h32a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm0 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2zm0 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2z"/>
                                            </svg>
                                            <span>{{ $faskes->nomor_telepon }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold border {{ $badgeClasses[$faskes->jenis_faskes] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $labels[$faskes->jenis_faskes] ?? $faskes->jenis_faskes }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <div class="font-semibold text-slate-800">Kec. {{ $faskes->kecamatan ?: '-' }}</div>
                                    <div class="text-[11px] text-slate-400">Desa {{ $faskes->desa ?: '-' }}</div>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                                    {{ $item['masa_izin']->format('d M Y') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] border {{ $statusPillClass }}">
                                        {{ $item['status_text'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <a href="{{ route('faskes.edit', $faskes->id) }}"
                                           class="px-2.5 py-1.5 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-600 font-bold rounded-lg text-[11px] transition-all flex items-center space-x-1 border border-slate-200/60 hover:border-blue-200"
                                           title="Perbarui Izin">
                                            <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span>Edit</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="font-medium text-slate-500 block text-xs">Tidak ada data faskes dengan tanggal masa izin yang tercatat.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. SECTION BARU: PEMANTAUAN IZIN OPERASIONAL (TUGAS 6) -->
        <div id="section-izin-operasional"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden scroll-mt-20"
             x-data="izinOperasionalTable()">
            
            <!-- Section Header -->
            <div class="p-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-500 to-amber-500 text-white flex items-center justify-center font-bold shadow-md shadow-rose-500/20 flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900 tracking-tight">Pemantauan Masa Berlaku Izin Operasional</h2>
                            <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-blue-100 text-blue-700">Faskes Aktif</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Sistem peringatan dini perizinan fasilitas kesehatan aktif di Kabupaten Banyumas</p>
                    </div>
                </div>

                <!-- Indikator Zona Waktu -->
                <div class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5 self-start lg:self-auto bg-white px-3 py-1.5 rounded-lg border border-slate-200/70 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Zona Waktu: <strong>WIB (Asia/Jakarta)</strong></span>
                </div>
            </div>

            <!-- Kartu Ringkasan Klik-untuk-Filter -->
            <div class="p-5 border-b border-slate-100 bg-slate-50/40">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    
                    <!-- 1. Kedaluwarsa -->
                    <button type="button"
                            @click="setLevel('kedaluwarsa')"
                            :class="activeLevel === 'kedaluwarsa' ? 'ring-2 ring-rose-950 border-rose-950 bg-rose-950 text-white' : 'bg-white border-slate-200/90 text-slate-800 hover:border-rose-400'"
                            class="p-3 rounded-xl border text-left transition-all shadow-2xs group flex flex-col justify-between cursor-pointer">
                        <div class="flex items-center justify-between">
                            <span :class="activeLevel === 'kedaluwarsa' ? 'text-rose-200' : 'text-rose-700'" class="text-[10px] font-bold uppercase tracking-wider">Kedaluwarsa</span>
                            <span class="w-2 h-2 rounded-full bg-rose-700"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black leading-none" x-text="ringkasan.kedaluwarsa"></span>
                            <span :class="activeLevel === 'kedaluwarsa' ? 'text-rose-200' : 'text-slate-400'" class="text-[10px]">Lewat masa</span>
                        </div>
                    </button>

                    <!-- 2. H-1 Bulan -->
                    <button type="button"
                            @click="setLevel('h_1_bulan')"
                            :class="activeLevel === 'h_1_bulan' ? 'ring-2 ring-rose-600 border-rose-600 bg-rose-600 text-white' : 'bg-white border-slate-200/90 text-slate-800 hover:border-rose-400'"
                            class="p-3 rounded-xl border text-left transition-all shadow-2xs group flex flex-col justify-between cursor-pointer">
                        <div class="flex items-center justify-between">
                            <span :class="activeLevel === 'h_1_bulan' ? 'text-rose-100' : 'text-rose-600'" class="text-[10px] font-bold uppercase tracking-wider">H-1 Bulan</span>
                            <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black leading-none" x-text="ringkasan.h_1_bulan"></span>
                            <span :class="activeLevel === 'h_1_bulan' ? 'text-rose-100' : 'text-slate-400'" class="text-[10px]">0 - 30 hari</span>
                        </div>
                    </button>

                    <!-- 3. H-3 Bulan -->
                    <button type="button"
                            @click="setLevel('h_3_bulan')"
                            :class="activeLevel === 'h_3_bulan' ? 'ring-2 ring-orange-500 border-orange-500 bg-orange-500 text-white' : 'bg-white border-slate-200/90 text-slate-800 hover:border-orange-400'"
                            class="p-3 rounded-xl border text-left transition-all shadow-2xs group flex flex-col justify-between cursor-pointer">
                        <div class="flex items-center justify-between">
                            <span :class="activeLevel === 'h_3_bulan' ? 'text-orange-100' : 'text-orange-600'" class="text-[10px] font-bold uppercase tracking-wider">H-3 Bulan</span>
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black leading-none" x-text="ringkasan.h_3_bulan"></span>
                            <span :class="activeLevel === 'h_3_bulan' ? 'text-orange-100' : 'text-slate-400'" class="text-[10px]">1 - 3 bulan</span>
                        </div>
                    </button>

                    <!-- 4. H-6 Bulan -->
                    <button type="button"
                            @click="setLevel('h_6_bulan')"
                            :class="activeLevel === 'h_6_bulan' ? 'ring-2 ring-amber-500 border-amber-500 bg-amber-500 text-white' : 'bg-white border-slate-200/90 text-slate-800 hover:border-amber-400'"
                            class="p-3 rounded-xl border text-left transition-all shadow-2xs group flex flex-col justify-between cursor-pointer">
                        <div class="flex items-center justify-between">
                            <span :class="activeLevel === 'h_6_bulan' ? 'text-amber-100' : 'text-amber-600'" class="text-[10px] font-bold uppercase tracking-wider">H-6 Bulan</span>
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black leading-none" x-text="ringkasan.h_6_bulan"></span>
                            <span :class="activeLevel === 'h_6_bulan' ? 'text-amber-100' : 'text-slate-400'" class="text-[10px]">3 - 6 bulan</span>
                        </div>
                    </button>

                    <!-- 5. Belum Diisi -->
                    <button type="button"
                            @click="setLevel('belum_diisi')"
                            :class="activeLevel === 'belum_diisi' ? 'ring-2 ring-slate-600 border-slate-600 bg-slate-700 text-white' : 'bg-white border-slate-200/90 text-slate-800 hover:border-slate-400'"
                            class="p-3 rounded-xl border text-left transition-all shadow-2xs group flex flex-col justify-between cursor-pointer">
                        <div class="flex items-center justify-between">
                            <span :class="activeLevel === 'belum_diisi' ? 'text-slate-200' : 'text-slate-500'" class="text-[10px] font-bold uppercase tracking-wider">Belum Diisi</span>
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black leading-none" x-text="ringkasan.belum_diisi"></span>
                            <span :class="activeLevel === 'belum_diisi' ? 'text-slate-200' : 'text-slate-400'" class="text-[10px]">Tanpa tanggal</span>
                        </div>
                    </button>

                    <!-- 6. Semua / Reset -->
                    <button type="button"
                            @click="setLevel('all')"
                            :class="activeLevel === 'all' ? 'ring-2 ring-blue-600 border-blue-600 bg-blue-600 text-white' : 'bg-white border-slate-200/90 text-slate-800 hover:border-blue-400'"
                            class="p-3 rounded-xl border text-left transition-all shadow-2xs group flex flex-col justify-between cursor-pointer">
                        <div class="flex items-center justify-between">
                            <span :class="activeLevel === 'all' ? 'text-blue-100' : 'text-blue-600'" class="text-[10px] font-bold uppercase tracking-wider">Semua Pantau</span>
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black leading-none" x-text="ringkasan.total_pantau"></span>
                            <span :class="activeLevel === 'all' ? 'text-blue-100' : 'text-slate-400'" class="text-[10px]">Total faskes</span>
                        </div>
                    </button>

                </div>
            </div>

            <!-- Toolbar Filter (Jenis Faskes & Kecamatan) -->
            <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-white">
                <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
                    
                    <!-- Dropdown Filter Jenis Faskes -->
                    <div class="w-48 sm:w-56">
                        <select x-model="selectedJenis"
                                @change="fetchData(1)"
                                class="w-full text-xs font-medium rounded-lg border-slate-300 focus:border-blue-500 focus:ring focus:ring-blue-200 py-1.5 px-2.5 bg-slate-50 hover:bg-white transition">
                            <option value="">Semua Jenis Terpantau</option>
                            <option value="puskesmas">Puskesmas</option>
                            <option value="rumah_sakit">Rumah Sakit</option>
                            <option value="klinik_pratama">Klinik Pratama</option>
                            <option value="klinik_utama">Klinik Utama</option>
                            <option value="laboratorium">Laboratorium</option>
                            <option value="griya_sehat">Griya Sehat</option>
                        </select>
                    </div>

                    <!-- Dropdown Filter Kecamatan -->
                    <div class="w-48 sm:w-56">
                        <select x-model="selectedKecamatan"
                                @change="fetchData(1)"
                                class="w-full text-xs font-medium rounded-lg border-slate-300 focus:border-blue-500 focus:ring focus:ring-blue-200 py-1.5 px-2.5 bg-slate-50 hover:bg-white transition">
                            <option value="">Semua Kecamatan</option>
                            @foreach([
                                'Ajibarang', 'Banyumas', 'Baturraden', 'Cilongok', 'Gumelar',
                                'Jatilawang', 'Kalibagor', 'Karanglewas', 'Kebasen', 'Kedungbanteng',
                                'Kembaran', 'Kemranjen', 'Lumbir', 'Patikraja', 'Pekuncen',
                                'Purwojati', 'Purwokerto Barat', 'Purwokerto Selatan', 'Purwokerto Timur', 'Purwokerto Utara',
                                'Rawalo', 'Sokaraja', 'Somagede', 'Sumbang', 'Sumpiuh',
                                'Tambak', 'Wangon'
                            ] as $kec)
                                <option value="{{ $kec }}">{{ $kec }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Reset Filter Button -->
                    <button type="button"
                            x-show="activeLevel !== 'all' || selectedJenis || selectedKecamatan"
                            @click="resetFilters()"
                            x-cloak
                            class="px-2.5 py-1.5 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition font-semibold flex items-center gap-1 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Reset Filter</span>
                    </button>
                </div>

                <!-- Info Jumlah Tampilan -->
                <div class="text-xs text-slate-500 font-medium">
                    Menampilkan <strong class="text-slate-800" x-text="totalItems"></strong> faskes terpantau
                </div>
            </div>

            <!-- Tabel Data Pemantauan Izin -->
            <div class="relative overflow-x-auto min-h-[220px]">
                
                <!-- Loading State Overlay -->
                <div x-show="isLoading"
                     x-cloak
                     class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <div class="flex items-center space-x-2 text-xs font-semibold text-slate-600 bg-white px-4 py-2 rounded-xl shadow-md border border-slate-200">
                        <svg class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Memuat data izin...</span>
                    </div>
                </div>

                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 uppercase tracking-wider font-bold">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">Nama Fasilitas Kesehatan</th>
                            <th class="py-3 px-4">Jenis Faskes</th>
                            <th class="py-3 px-4">Kecamatan</th>
                            <th class="py-3 px-4">Tanggal Masa Izin</th>
                            <th class="py-3 px-4">Sisa Waktu</th>
                            <th class="py-3 px-4 text-center">Tingkat Peringatan</th>
                            <th class="py-3 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Data Rows via Alpine -->
                        <template x-for="(item, idx) in items" :key="item.id">
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 text-center font-bold text-slate-400" x-text="fromItem + idx"></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 text-sm leading-snug" x-text="item.nama"></div>
                                    <div x-show="item.nomor_telepon" class="text-[11px] text-slate-400 font-medium flex items-center space-x-1 mt-0.5">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h32a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V5z" />
                                        </svg>
                                        <span x-text="item.nomor_telepon"></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200"
                                          x-text="item.jenis_label"></span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <div class="font-semibold text-slate-800" x-text="item.kecamatan ? 'Kec. ' + item.kecamatan : '-'"></div>
                                    <div class="text-[10px] text-slate-400" x-text="item.desa ? 'Desa ' + item.desa : ''"></div>
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-700" x-text="item.masa_izin_formatted"></td>
                                <td class="py-3 px-4 font-bold"
                                    :class="item.level === 'kedaluwarsa' ? 'text-rose-700' : (item.level === 'h_1_bulan' ? 'text-rose-600' : (item.level === 'h_3_bulan' ? 'text-orange-600' : (item.level === 'h_6_bulan' ? 'text-amber-600' : 'text-slate-500')))"
                                    x-text="item.sisa_waktu_text"></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px]"
                                          :class="item.badge_class"
                                          x-text="item.level_label"></span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a :href="item.edit_url"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-600 font-bold rounded-lg text-[11px] transition border border-slate-200/70 hover:border-blue-200">
                                        <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span>Perbarui</span>
                                    </a>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!isLoading && items.length === 0" x-cloak>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto mb-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="font-bold text-slate-700 block text-xs">Semua izin masih aman</span>
                                <span class="text-[11px] text-slate-400 mt-0.5 block">Tidak ada fasilitas kesehatan yang masuk dalam kategori filter ini.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <div class="text-slate-500">
                    Menampilkan <span class="font-bold text-slate-800" x-text="fromItem"></span> sampai <span class="font-bold text-slate-800" x-text="toItem"></span> dari <span class="font-bold text-slate-800" x-text="totalItems"></span> faskes
                </div>
                <div class="flex items-center gap-1.5 self-end sm:self-auto" x-show="lastPage > 1">
                    <button type="button"
                            @click="fetchData(currentPage - 1)"
                            :disabled="currentPage <= 1 || isLoading"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium text-slate-700 transition cursor-pointer">
                        &larr; Sebelumnya
                    </button>
                    <span class="px-3 py-1.5 font-bold text-slate-700">
                        Hal <span x-text="currentPage"></span> / <span x-text="lastPage"></span>
                    </span>
                    <button type="button"
                            @click="fetchData(currentPage + 1)"
                            :disabled="currentPage >= lastPage || isLoading"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium text-slate-700 transition cursor-pointer">
                        Selanjutnya &rarr;
                    </button>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200/80 py-4 text-center text-xs text-slate-400 font-medium">
        &copy; {{ date('Y') }} Sistem Informasi Geografis Faskes &bull; Dinas Kesehatan Kabupaten Banyumas
    </footer>

    <!-- Chart.js Scripts Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Inisialisasi Bar Chart: Faskes per Kecamatan
            const ctxKecamatan = document.getElementById('chartKecamatan').getContext('2d');
            const labelsKecamatan = @json($chartKecamatanLabels);
            const dataKecamatan = @json($chartKecamatanData);

            new Chart(ctxKecamatan, {
                type: 'bar',
                data: {
                    labels: labelsKecamatan,
                    datasets: [{
                        label: 'Jumlah Faskes',
                        data: dataKecamatan,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                            titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                            callbacks: {
                                label: function(context) {
                                    return ` ${context.parsed.y} Fasilitas Kesehatan`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: { family: 'Plus Jakarta Sans', size: 10, weight: '500' },
                                color: '#64748b'
                            },
                            grid: {
                                color: '#f1f5f9'
                            }
                        },
                        x: {
                            ticks: {
                                font: { family: 'Plus Jakarta Sans', size: 10, weight: '500' },
                                color: '#64748b'
                            },
                            grid: { display: false }
                        }
                    }
                }
            });

            // 2. Inisialisasi Donut Chart: Persentase Kategori Faskes
            const ctxKategori = document.getElementById('chartKategori').getContext('2d');
            const labelsKategori = @json($chartKategoriLabels);
            const dataKategori = @json($chartKategoriData);
            const colorsKategori = @json($chartKategoriColors);

            new Chart(ctxKategori, {
                type: 'doughnut',
                data: {
                    labels: labelsKategori,
                    datasets: [{
                        data: dataKategori,
                        backgroundColor: colorsKategori,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                padding: 14,
                                usePointStyle: true,
                                font: { family: 'Plus Jakarta Sans', size: 10, weight: '600' },
                                color: '#475569'
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                            titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 11 },
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const value = context.raw;
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return ` ${context.label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });

        // Alpine.js Component untuk Pemantauan Izin Operasional (Tugas 6)
        function izinOperasionalTable() {
            return {
                activeLevel: 'all',
                selectedJenis: '',
                selectedKecamatan: '',
                currentPage: 1,
                perPage: 10,
                lastPage: 1,
                totalItems: 0,
                fromItem: 0,
                toItem: 0,
                items: [],
                ringkasan: @json($ringkasanIzin),
                isLoading: false,

                init() {
                    this.fetchData(1);
                },

                setLevel(level) {
                    if (this.activeLevel === level) {
                        this.activeLevel = 'all';
                    } else {
                        this.activeLevel = level;
                    }
                    this.fetchData(1);
                },

                resetFilters() {
                    this.activeLevel = 'all';
                    this.selectedJenis = '';
                    this.selectedKecamatan = '';
                    this.fetchData(1);
                },

                fetchData(page = 1) {
                    this.isLoading = true;
                    this.currentPage = page;

                    const params = new URLSearchParams();
                    if (this.activeLevel && this.activeLevel !== 'all') params.append('level', this.activeLevel);
                    if (this.selectedJenis) params.append('jenis_faskes', this.selectedJenis);
                    if (this.selectedKecamatan) params.append('kecamatan', this.selectedKecamatan);
                    params.append('page', this.currentPage);
                    params.append('per_page', this.perPage);

                    fetch("{{ route('dashboard.izin-operasional') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Gagal memuat data izin operasional');
                        return res.json();
                    })
                    .then(res => {
                        this.items = res.data || [];
                        if (res.ringkasan) {
                            this.ringkasan = res.ringkasan;
                        }
                        if (res.pagination) {
                            this.currentPage = res.pagination.current_page;
                            this.lastPage = res.pagination.last_page;
                            this.totalItems = res.pagination.total;
                            this.fromItem = res.pagination.from;
                            this.toItem = res.pagination.to;
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching izin operasional:', err);
                    })
                    .finally(() => {
                        this.isLoading = false;
                    });
                }
            };
        }
    </script>
</body>
</html>