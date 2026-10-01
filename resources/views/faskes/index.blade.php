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
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans"
      x-data="faskesIndexApp({
          search: {{ json_encode(request('search', '')) }},
          jenis_faskes: {{ json_encode(request('jenis_faskes', '')) }},
          status: {{ json_encode(request('status', '')) }},
          kecamatan: {{ json_encode(request('kecamatan', '')) }},
          kepemilikan: {{ json_encode(request('kepemilikan', '')) }},
          bpjs: {{ json_encode(request('bpjs', '')) }},
          has_ambulans: {{ json_encode(request('has_ambulans', '')) }},
          has_bed: {{ json_encode(request('has_bed', '')) }},
          persalinan: {{ json_encode(request('persalinan', '')) }},
          tipe_rs: {{ json_encode(request('tipe_rs', '')) }},
          status_izin: {{ json_encode(request('status_izin', '')) }},
          custom_filter: {{ json_encode(request('custom_filter', [])) }}
      })">

    <!-- Header Navigation -->
    <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-30 shadow-xs">
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
                <a id="btn-export-excel" href="{{ route('faskes.export.excel', request()->query()) }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center gap-1.5 shadow-sm shadow-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-8 4h10a2 2 0 002-2V6a2 2 0 00-2-2H9a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Export Excel</span>
                </a>

                <!-- Export PDF Button -->
                <a id="btn-export-pdf" href="{{ route('faskes.export.pdf', request()->query()) }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition flex items-center gap-1.5 shadow-sm shadow-rose-500/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14v5a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v1M9 13h1.5a1.5 1.5 0 010 3H9v-3zm0 3v2m5-5v5m0-5h1.25a1.25 1.25 0 010 2.5H14m0 0V19" />
                    </svg>
                    <span>Export PDF</span>
                </a>

                <!-- Kelola Kolom Tambahan Button -->
                <a href="{{ route('faskes-fields.index') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                    <span>Kelola Kolom Tambahan</span>
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

        <!-- Client-Side Toast Notification for Bulk Actions -->
        <div x-show="bulkDeleteSuccessMsg" x-cloak
             class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium" x-text="bulkDeleteSuccessMsg"></span>
            </div>
            <button @click="bulkDeleteSuccessMsg = ''" class="text-emerald-500 hover:text-emerald-700 p-0.5 rounded transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

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

        <!-- Filter & Search Card (Tugas 3B) -->
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm space-y-3">
            <form action="{{ route('faskes.index') }}" method="GET" @submit.prevent="fetchTable()" class="space-y-3">
                <!-- Baris Filter Utama -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cari Nama / Alamat</label>
                        <div class="relative">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input type="text"
                                   name="search"
                                   x-model="filters.search"
                                   @input="onSearchInput()"
                                   placeholder="Cari faskes, kecamatan, desa..."
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>
                    </div>

                    <!-- Filter Jenis Faskes -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Jenis Faskes</label>
                        <select name="jenis_faskes"
                                x-model="filters.jenis_faskes"
                                @change="onDropdownChange()"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua Jenis</option>
                            <option value="rumah_sakit">Rumah Sakit</option>
                            <option value="puskesmas">Puskesmas</option>
                            <option value="klinik_pratama">Klinik Pratama</option>
                            <option value="klinik_utama">Klinik Utama</option>
                            <option value="laboratorium">Laboratorium</option>
                            <option value="upkdk">UPKDK (Pustu / PKD)</option>
                            <option value="griya_sehat">Griya Sehat</option>
                            <option value="tpmd">TPMD (Praktik Mandiri Dokter)</option>
                            <option value="tpmdg">TPMDG (Praktik Mandiri Dokter Gigi)</option>
                            <option value="tpmb">TPMB (Praktik Mandiri Bidan)</option>
                            <option value="tpmp">TPMP (Praktik Mandiri Perawat)</option>
                        </select>
                    </div>

                    <!-- Filter Status -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                        <select name="status"
                                x-model="filters.status"
                                @change="onDropdownChange()"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-end gap-2">
                        <!-- Toggle Filter Lanjutan -->
                        <button type="button"
                                @click="advancedFilterOpen = !advancedFilterOpen"
                                class="flex-1 py-2 px-3 border border-slate-200 hover:border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold rounded-lg text-xs transition flex items-center justify-center gap-1.5"
                                :class="{ 'bg-blue-50 border-blue-300 text-blue-700': advancedFilterOpen || activeFilterCount > 0 }">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                            <span>Filter Lanjutan</span>
                            <span x-show="activeFilterCount > 0"
                                  x-text="activeFilterCount"
                                  class="ml-1 px-1.5 py-0.2 rounded-full bg-blue-600 text-white text-[10px] font-bold"></span>
                        </button>

                        <!-- Tombol Reset -->
                        <button type="button"
                                @click="resetFilters()"
                                class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-lg text-xs transition flex items-center justify-center gap-1.5"
                                title="Reset Semua Filter">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>

                <!-- Collapsible Panel: Filter Lanjutan (Tugas 3B) -->
                <div x-show="advancedFilterOpen" x-cloak
                     class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 bg-slate-50/70 p-3.5 rounded-xl border border-slate-200/80">
                    
                    <!-- 1. Kecamatan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Kecamatan</label>
                        <select name="kecamatan"
                                x-model="filters.kecamatan"
                                @change="onDropdownChange()"
                                class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua Kecamatan</option>
                            @foreach($kecamatanList as $kec)
                                <option value="{{ $kec }}">{{ $kec }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Kepemilikan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Kepemilikan</label>
                        <select name="kepemilikan"
                                x-model="filters.kepemilikan"
                                @change="onDropdownChange()"
                                class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua Kepemilikan</option>
                            <option value="Swasta">Swasta</option>
                            <option value="Pemerintah">Pemerintah</option>
                        </select>
                    </div>

                    <!-- 3. BPJS -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Kerja Sama BPJS</label>
                        <select name="bpjs"
                                x-model="filters.bpjs"
                                @change="onDropdownChange()"
                                class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua</option>
                            <option value="ya">Ya (Bekerja sama)</option>
                            <option value="tidak">Tidak</option>
                        </select>
                    </div>

                    <!-- 4. Memiliki Ambulans -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Memiliki Ambulans</label>
                        <select name="has_ambulans"
                                x-model="filters.has_ambulans"
                                @change="onDropdownChange()"
                                class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua</option>
                            <option value="ya">Memiliki Ambulans</option>
                            <option value="tidak">Tanpa Ambulans</option>
                        </select>
                    </div>

                    <!-- 5. Memiliki Bed Rawat Inap -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bed Rawat Inap</label>
                        <select name="has_bed"
                                x-model="filters.has_bed"
                                @change="onDropdownChange()"
                                class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua</option>
                            <option value="ya">Ada Bed Rawat Inap</option>
                            <option value="tidak">Tanpa Rawat Inap</option>
                        </select>
                    </div>

                    <!-- 6. Persalinan Puskesmas -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Persalinan Puskesmas</label>
                        <select name="persalinan"
                                x-model="filters.persalinan"
                                @change="onDropdownChange()"
                                class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua</option>
                            <option value="poned">PONED</option>
                            <option value="mampu_salin">NON PONED Mampu Salin</option>
                            <option value="tidak_mampu_salin">NON PONED Tidak Mampu Salin</option>
                        </select>
                    </div>

                    <!-- 7. Tipe RS -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tipe Rumah Sakit</label>
                        <select name="tipe_rs"
                                x-model="filters.tipe_rs"
                                @change="onDropdownChange()"
                                class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua Tipe RS</option>
                            <option value="A">Kelas A</option>
                            <option value="B">Kelas B</option>
                            <option value="C">Kelas C</option>
                            <option value="D">Kelas D</option>
                            <option value="D Pratama">Kelas D Pratama</option>
                        </select>
                    </div>

                    <!-- 8. Status Izin Operasional -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status Izin Operasional</label>
                        <select name="status_izin"
                                x-model="filters.status_izin"
                                @change="onDropdownChange()"
                                class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua Status Izin</option>
                            <option value="berlaku">Berlaku (&gt; 90 hari)</option>
                            <option value="segera_berakhir">Segera Berakhir (&le; 90 hari)</option>
                            <option value="kedaluwarsa">Kedaluwarsa</option>
                        </select>
                    </div>

                    <!-- 9. Kolom Tambahan Dinamis (dari Tugas 2) -->
                    @if(isset($activeFieldDefinitions) && $activeFieldDefinitions->count() > 0)
                        @foreach($activeFieldDefinitions as $def)
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    {{ $def->label }}
                                    @if($def->jenis_faskes)
                                        <span class="text-[10px] text-slate-400 font-normal">({{ $def->jenis_faskes }})</span>
                                    @endif
                                </label>
                                <select x-model="filters.custom_filter['{{ $def->field_key }}']"
                                        @change="onDropdownChange()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                    <option value="">Semua {{ $def->label }}</option>
                                    @foreach($def->options as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    @endif

                </div>
            </form>
        </div>

        <!-- Action Bar Pilihan Massal (Tugas 3C) -->
        <div x-show="selectedIds.length > 0" x-cloak
             class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 flex flex-wrap items-center justify-between gap-3 text-xs shadow-xs transition">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white font-bold text-xs" x-text="selectedIds.length"></span>
                <span class="font-bold text-blue-900"><span x-text="selectedIds.length"></span> faskes terpilih</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="clearSelection()"
                        class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">
                    Batal Pilih
                </button>
                <button type="button" @click="bulkDeleteModalOpen = true"
                        class="px-3.5 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition flex items-center gap-1.5 shadow-sm shadow-red-500/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    <span>Hapus Terpilih</span>
                </button>
            </div>
        </div>

        <!-- Table Container with AJAX update & loading indicator (Tugas 3B & 3C) -->
        <div class="relative">
            <!-- Loading Indicator Overlay -->
            <div x-show="isLoadingTable" x-cloak
                 class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-10 flex items-center justify-center pointer-events-none transition-opacity duration-200">
                <div class="flex items-center gap-2.5 px-4 py-2.5 bg-slate-900/80 text-white rounded-xl text-xs font-semibold shadow-xl backdrop-blur-sm">
                    <svg class="animate-spin w-4 h-4 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memuat data faskes...</span>
                </div>
            </div>

            <!-- Table Container (menampung partial faskes._table) -->
            <div id="table-container" :class="{ 'opacity-50 pointer-events-none transition-opacity duration-200': isLoadingTable }">
                @include('faskes._table')
            </div>
        </div>

    </main>

    <!-- Modal Konfirmasi Hapus Massal (Tugas 3C) -->
    <div x-show="bulkDeleteModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="if (!isBulkDeleting) bulkDeleteModalOpen = false"
             class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100">
            <div class="flex items-center gap-3 text-red-600">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Konfirmasi Hapus Massal</h3>
                    <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>

            <!-- Peringatan & Detail Pilihan -->
            <div class="space-y-2 text-xs text-slate-600">
                <p>
                    Anda akan menghapus <span class="font-bold text-red-600" x-text="selectedIds.length"></span> data fasilitas kesehatan berikut:
                </p>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5 max-h-40 overflow-y-auto text-[11px] space-y-1">
                    <template x-for="(nama, idx) in previewNames" :key="idx">
                        <div class="flex items-center gap-1.5 text-slate-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                            <span class="truncate" x-text="nama"></span>
                        </div>
                    </template>
                    <div x-show="selectedIds.length > 10" class="text-slate-400 italic pt-1 pl-3 text-[10px]">
                        ... dan <span x-text="selectedIds.length - 10"></span> faskes lainnya.
                    </div>
                </div>
                <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2 flex items-start gap-1.5">
                    <svg class="w-4 h-4 flex-shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Perhatian: Seluruh data spesifikasi detail dan nilai kolom tambahan yang berkaitan juga akan ikut dihapus permanen.</span>
                </p>
            </div>

            <!-- Error message if any -->
            <div x-show="bulkDeleteError" x-cloak class="p-2.5 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                <span x-text="bulkDeleteError"></span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button"
                        @click="bulkDeleteModalOpen = false"
                        :disabled="isBulkDeleting"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition disabled:opacity-50">
                    Batal
                </button>
                <button type="button"
                        @click="submitBulkDelete()"
                        :disabled="isBulkDeleting"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm shadow-red-500/25 disabled:opacity-50">
                    <svg x-show="isBulkDeleting" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isBulkDeleting ? 'Menghapus...' : 'Ya, Hapus ' + selectedIds.length + ' Faskes'"></span>
                </button>
            </div>
        </div>
    </div>

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

    <!-- Alpine Component Script for Index Page (Tugas 3B & 3C) -->
    <script>
        function faskesIndexApp(initialFilters) {
            return {
                importModalOpen: false,
                advancedFilterOpen: false,
                bulkDeleteModalOpen: false,
                isLoadingTable: false,
                isBulkDeleting: false,
                bulkDeleteError: '',
                bulkDeleteSuccessMsg: '',
                tableAbortController: null,
                searchDebounceTimer: null,
                selectedIds: [],
                selectedItems: {}, // id -> nama

                filters: {
                    search: initialFilters.search || '',
                    jenis_faskes: initialFilters.jenis_faskes || '',
                    status: initialFilters.status || '',
                    kecamatan: initialFilters.kecamatan || '',
                    kepemilikan: initialFilters.kepemilikan || '',
                    bpjs: initialFilters.bpjs || '',
                    has_ambulans: initialFilters.has_ambulans || '',
                    has_bed: initialFilters.has_bed || '',
                    persalinan: initialFilters.persalinan || '',
                    tipe_rs: initialFilters.tipe_rs || '',
                    status_izin: initialFilters.status_izin || '',
                    custom_filter: initialFilters.custom_filter || {}
                },

                init() {
                    // Jika ada filter lanjutan yang aktif saat halaman dimuat, buka panel filter lanjutan
                    if (this.activeFilterCount > 0) {
                        this.advancedFilterOpen = true;
                    }
                    this.$nextTick(() => {
                        this.attachPaginationListeners();
                    });
                },

                // Hitung jumlah filter lanjutan yang sedang aktif
                get activeFilterCount() {
                    let count = 0;
                    if (this.filters.kecamatan) count++;
                    if (this.filters.kepemilikan) count++;
                    if (this.filters.bpjs) count++;
                    if (this.filters.has_ambulans) count++;
                    if (this.filters.has_bed) count++;
                    if (this.filters.persalinan) count++;
                    if (this.filters.tipe_rs) count++;
                    if (this.filters.status_izin) count++;
                    if (this.filters.custom_filter) {
                        for (const key in this.filters.custom_filter) {
                            if (this.filters.custom_filter[key]) count++;
                        }
                    }
                    return count;
                },

                // Preview nama-nama faskes yang dipilih (maks 10 untuk modal)
                get previewNames() {
                    return Object.values(this.selectedItems).slice(0, 10);
                },

                // Status checkbox header "Pilih Semua di halaman ini"
                get isAllSelected() {
                    const checkboxes = document.querySelectorAll('#table-container .row-checkbox');
                    if (!checkboxes.length) return false;
                    return Array.from(checkboxes).every(cb => this.selectedIds.includes(parseInt(cb.dataset.id)));
                },

                get isIndeterminate() {
                    const checkboxes = document.querySelectorAll('#table-container .row-checkbox');
                    if (!checkboxes.length) return false;
                    const count = Array.from(checkboxes).filter(cb => this.selectedIds.includes(parseInt(cb.dataset.id))).length;
                    return count > 0 && count < checkboxes.length;
                },

                // Input Search Debounce 400ms (Tugas 3B)
                onSearchInput() {
                    clearTimeout(this.searchDebounceTimer);
                    this.searchDebounceTimer = setTimeout(() => {
                        this.fetchTable();
                    }, 400);
                },

                // Dropdown Onchange langsung (Tugas 3B)
                onDropdownChange() {
                    this.fetchTable();
                },

                // Reset Semua Filter
                resetFilters() {
                    this.filters.search = '';
                    this.filters.jenis_faskes = '';
                    this.filters.status = '';
                    this.filters.kecamatan = '';
                    this.filters.kepemilikan = '';
                    this.filters.bpjs = '';
                    this.filters.has_ambulans = '';
                    this.filters.has_bed = '';
                    this.filters.persalinan = '';
                    this.filters.tipe_rs = '';
                    this.filters.status_izin = '';
                    for (const key in this.filters.custom_filter) {
                        this.filters.custom_filter[key] = '';
                    }
                    this.fetchTable();
                },

                // Fetch Table via AJAX (Tugas 3B)
                fetchTable(customUrl = null) {
                    if (this.tableAbortController) {
                        this.tableAbortController.abort();
                    }
                    this.tableAbortController = new AbortController();

                    let targetUrl;
                    if (customUrl) {
                        targetUrl = customUrl;
                    } else {
                        const params = new URLSearchParams();
                        if (this.filters.search) params.append('search', this.filters.search);
                        if (this.filters.jenis_faskes) params.append('jenis_faskes', this.filters.jenis_faskes);
                        if (this.filters.status) params.append('status', this.filters.status);
                        if (this.filters.kecamatan) params.append('kecamatan', this.filters.kecamatan);
                        if (this.filters.kepemilikan) params.append('kepemilikan', this.filters.kepemilikan);
                        if (this.filters.bpjs) params.append('bpjs', this.filters.bpjs);
                        if (this.filters.has_ambulans) params.append('has_ambulans', this.filters.has_ambulans);
                        if (this.filters.has_bed) params.append('has_bed', this.filters.has_bed);
                        if (this.filters.persalinan) params.append('persalinan', this.filters.persalinan);
                        if (this.filters.tipe_rs) params.append('tipe_rs', this.filters.tipe_rs);
                        if (this.filters.status_izin) params.append('status_izin', this.filters.status_izin);
                        if (this.filters.custom_filter) {
                            for (const [k, v] of Object.entries(this.filters.custom_filter)) {
                                if (v) params.append(`custom_filter[${k}]`, v);
                            }
                        }
                        const qs = params.toString();
                        targetUrl = window.location.pathname + (qs ? '?' + qs : '');
                    }

                    // Sinkronisasi URL browser tanpa reload (Tugas 3B)
                    window.history.replaceState(null, '', targetUrl);

                    // Update tombol export excel & pdf
                    const searchParamsStr = window.location.search;
                    const excelBtn = document.getElementById('btn-export-excel');
                    if (excelBtn) {
                        const baseExcel = "{{ route('faskes.export.excel') }}";
                        excelBtn.href = baseExcel + searchParamsStr;
                    }
                    const pdfBtn = document.getElementById('btn-export-pdf');
                    if (pdfBtn) {
                        const basePdf = "{{ route('faskes.export.pdf') }}";
                        pdfBtn.href = basePdf + searchParamsStr;
                    }

                    this.isLoadingTable = true;

                    fetch(targetUrl, {
                        signal: this.tableAbortController.signal,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Gagal memuat data faskes');
                        return response.text();
                    })
                    .then(html => {
                        const container = document.getElementById('table-container');
                        if (container) {
                            container.innerHTML = html;
                        }
                        this.attachPaginationListeners();
                        this.isLoadingTable = false;
                    })
                    .catch(err => {
                        if (err.name === 'AbortError') return;
                        console.error('Error fetching faskes table:', err);
                        this.isLoadingTable = false;
                    });
                },

                // Intercept Pagination Links agar jalan via AJAX (Tugas 3B)
                attachPaginationListeners() {
                    const pagination = document.getElementById('table-pagination');
                    if (!pagination) return;
                    const links = pagination.querySelectorAll('a');
                    links.forEach(link => {
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            const href = link.getAttribute('href');
                            if (href) {
                                this.fetchTable(href);
                            }
                        });
                    });
                },

                // Checkbox All Toggle (Tugas 3C)
                toggleSelectAll(event) {
                    const checkboxes = document.querySelectorAll('#table-container .row-checkbox');
                    if (event.target.checked) {
                        checkboxes.forEach(cb => {
                            const id = parseInt(cb.dataset.id);
                            const nama = cb.dataset.nama;
                            if (!this.selectedIds.includes(id)) {
                                this.selectedIds.push(id);
                                this.selectedItems[id] = nama;
                            }
                        });
                    } else {
                        checkboxes.forEach(cb => {
                            const id = parseInt(cb.dataset.id);
                            const idx = this.selectedIds.indexOf(id);
                            if (idx !== -1) {
                                this.selectedIds.splice(idx, 1);
                                delete this.selectedItems[id];
                            }
                        });
                    }
                },

                // Checkbox Single Item Toggle (Tugas 3C)
                toggleItem(id, nama) {
                    id = parseInt(id);
                    const idx = this.selectedIds.indexOf(id);
                    if (idx !== -1) {
                        this.selectedIds.splice(idx, 1);
                        delete this.selectedItems[id];
                    } else {
                        this.selectedIds.push(id);
                        this.selectedItems[id] = nama;
                    }
                },

                // Batal Pilih (Tugas 3C)
                clearSelection() {
                    this.selectedIds = [];
                    this.selectedItems = {};
                },

                // Submit Bulk Delete via AJAX (Tugas 3C)
                submitBulkDelete() {
                    if (this.selectedIds.length === 0 || this.isBulkDeleting) return;

                    this.isBulkDeleting = true;
                    this.bulkDeleteError = '';

                    fetch("{{ route('faskes.bulk-destroy') }}", {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: this.selectedIds
                        })
                    })
                    .then(async response => {
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || 'Gagal menghapus faskes terpilih');
                        }
                        return data;
                    })
                    .then(data => {
                        this.bulkDeleteSuccessMsg = data.message || 'Faskes terpilih berhasil dihapus.';
                        this.clearSelection();
                        this.bulkDeleteModalOpen = false;
                        this.fetchTable();
                    })
                    .catch(err => {
                        console.error('Bulk delete error:', err);
                        this.bulkDeleteError = err.message || 'Terjadi kesalahan sistem saat menghapus data.';
                    })
                    .finally(() => {
                        this.isBulkDeleting = false;
                    });
                }
            };
        }
    </script>
</body>
</html>