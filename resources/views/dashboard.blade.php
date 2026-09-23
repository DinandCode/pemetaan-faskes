<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analitik Fasilitas Kesehatan - Kab. Banyumas</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans">

    <!-- Header Navigation -->
    <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-500/20">
                    📊
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-800 leading-tight">Dashboard Analitik Faskes</h1>
                    <p class="text-xs text-slate-500">Dinas Kesehatan Kabupaten Banyumas &bull; Monitoring Pelayanan & Perizinan</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 border border-blue-200 flex items-center space-x-1.5 transition">
                    <span>📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="{{ url('/peta') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center space-x-1.5">
                    <span>🗺️</span>
                    <span>Peta GIS</span>
                </a>
                <a href="{{ route('faskes.index') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center space-x-1.5">
                    <span>📋</span>
                    <span>Master Faskes</span>
                </a>
                <a href="{{ route('faskes.create') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 transition flex items-center space-x-1.5">
                    <span>➕</span>
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
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center space-x-3.5 hover:border-blue-300 transition">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                    🏥
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Faskes</span>
                    <span class="text-2xl font-extrabold text-slate-900 leading-none">{{ $totalFaskes }}</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Kabupaten Banyumas</span>
                </div>
            </div>

            <!-- Card 2: Total Puskesmas -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center space-x-3.5 hover:border-sky-300 transition">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                    🏢
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Puskesmas</span>
                    <span class="text-2xl font-extrabold text-sky-700 leading-none">{{ $totalPuskesmas }}</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Pusat Kesehatan Masy.</span>
                </div>
            </div>

            <!-- Card 3: Total Rumah Sakit -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center space-x-3.5 hover:border-red-300 transition">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                    🏨
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Rumah Sakit</span>
                    <span class="text-2xl font-extrabold text-red-600 leading-none">{{ $totalRumahSakit }}</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">RSUD & Rujukan</span>
                </div>
            </div>

            <!-- Card 4: Total Bed Rawat Inap -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center space-x-3.5 hover:border-emerald-300 transition">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                    🛏️
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Bed Rawat Inap</span>
                    <span class="text-2xl font-extrabold text-emerald-600 leading-none">{{ $totalBed }}</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Kapasitas Tempat Tidur</span>
                </div>
            </div>

            <!-- Card 5: Total Ambulans Aktif -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center space-x-3.5 hover:border-amber-300 transition">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                    🚑
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Ambulans Aktif</span>
                    <span class="text-2xl font-extrabold text-amber-600 leading-none">{{ $totalAmbulans }}</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Faskes Siaga Rujukan</span>
                </div>
            </div>

        </div>

        <!-- 2. GRAFIK STATISTIK (CHART.JS) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Grafik 1: Faskes per Kecamatan (Bar Chart) - 2 Kolom -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Jumlah Faskes per Kecamatan</h2>
                        <p class="text-[11px] text-slate-400">Sebaran fasilitas kesehatan di wilayah Kabupaten Banyumas</p>
                    </div>
                    <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs font-semibold">
                        {{ count($chartKecamatanLabels) }} Kecamatan
                    </span>
                </div>
                <div class="relative flex-1 min-h-[280px]">
                    <canvas id="chartKecamatan"></canvas>
                </div>
            </div>

            <!-- Grafik 2: Persentase Kategori Faskes (Donut Chart) - 1 Kolom -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Persentase Kategori Faskes</h2>
                        <p class="text-[11px] text-slate-400">Proporsi jenis faskes terdaftar</p>
                    </div>
                    <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold">
                        {{ $totalFaskes }} Total
                    </span>
                </div>
                <div class="relative flex-1 min-h-[280px] flex items-center justify-center">
                    <canvas id="chartKategori"></canvas>
                </div>
            </div>

        </div>

        <!-- 3. TABEL RINGKASAN: 5 FASKES DENGAN IZIN MENDEKATI KADALUARSA -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50/50">
                <div class="flex items-center space-x-2.5">
                    <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                        ⏳
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Monitoring Masa Berlaku Izin Operasional Faskes</h2>
                        <p class="text-[11px] text-slate-500">5 fasilitas kesehatan dengan tanggal izin terdekat untuk tindak lanjut perpanjangan</p>
                    </div>
                </div>
                <a href="{{ route('faskes.index') }}" class="text-xs text-blue-600 hover:text-blue-700 font-semibold flex items-center space-x-1">
                    <span>Lihat Semua Faskes</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">Nama Fasilitas Kesehatan</th>
                            <th class="py-3 px-4">Jenis Faskes</th>
                            <th class="py-3 px-4">Kecamatan / Wilayah</th>
                            <th class="py-3 px-4">Tanggal Masa Izin</th>
                            <th class="py-3 px-4">Status & Sisa Waktu</th>
                            <th class="py-3 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($allWithIzin as $idx => $item)
                            @php
                                $faskes = $item['faskes'];
                                $badgeClasses = [
                                    'rumah_sakit'    => 'bg-red-50 text-red-700 border-red-200',
                                    'puskesmas'      => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'klinik_pratama' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'klinik_utama'   => 'bg-teal-50 text-teal-700 border-teal-200',
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

                                $statusPillClass = match($item['status_badge']) {
                                    'expired'  => 'bg-red-100 text-red-800 border-red-200',
                                    'critical' => 'bg-rose-100 text-rose-800 border-rose-200 font-bold',
                                    'warning'  => 'bg-amber-100 text-amber-800 border-amber-200',
                                    default    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 text-center font-semibold text-slate-500">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $faskes->nama }}</div>
                                    @if($faskes->nomor_telepon)
                                        <div class="text-[11px] text-slate-400">📞 {{ $faskes->nomor_telepon }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClasses[$faskes->jenis_faskes] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $labels[$faskes->jenis_faskes] ?? $faskes->jenis_faskes }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <div>Kec. {{ $faskes->kecamatan ?: '-' }}</div>
                                    <div class="text-[11px] text-slate-400">Desa {{ $faskes->desa ?: '-' }}</div>
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-800">
                                    {{ $item['masa_izin']->format('d M Y') }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] border {{ $statusPillClass }}">
                                        {{ $item['status_text'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <a href="{{ route('faskes.edit', $faskes->id) }}"
                                           class="px-2.5 py-1 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-600 font-semibold rounded-lg text-xs transition"
                                           title="Perbarui Izin">
                                            ✏️ Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    <span class="text-2xl block mb-1">📋</span>
                                    <span>Tidak ada data faskes dengan tanggal masa izin yang tercatat.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-400">
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
                                font: { size: 10 }
                            },
                            grid: {
                                color: '#f1f5f9'
                            }
                        },
                        x: {
                            ticks: {
                                font: { size: 10 }
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
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                padding: 12,
                                font: { size: 10, weight: '500' }
                            }
                        },
                        tooltip: {
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
