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
    </script>
</body>
</html>