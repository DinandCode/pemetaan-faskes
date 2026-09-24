<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <title>Sistem Informasi Geografis - Pemetaan Faskes & Rute Darurat</title>
    <link rel="icon" href="data:;base64,iVBORw0KGgo=">

    <!-- Leaflet CSS (Di-import SEBELUM Tailwind CSS agar tidak bentrok atau terkena reset CSS) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        html, body {
            height: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        /* Perbaikan CSS Leaflet - Hilangkan kotak hitam & border default */
        .leaflet-container {
            font-family: inherit !important;
        }
        path.leaflet-interactive:focus,
        .leaflet-container path:focus {
            outline: none !important;
        }
        .leaflet-tooltip {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
            color: #1e293b !important;
        }
        .leaflet-tooltip-top:before { border-top-color: rgba(255, 255, 255, 0.95) !important; }
        .leaflet-tooltip-bottom:before { border-bottom-color: rgba(255, 255, 255, 0.95) !important; }
        .leaflet-tooltip-left:before { border-left-color: rgba(255, 255, 255, 0.95) !important; }
        .leaflet-tooltip-right:before { border-right-color: rgba(255, 255, 255, 0.95) !important; }
        .leaflet-div-icon { background: transparent !important; border: none !important; }

        /* Rapikan tombol zoom Leaflet supaya konsisten dengan gaya UI Tailwind di sekitarnya */
        .leaflet-touch .leaflet-bar a {
            width: 32px;
            height: 32px;
            line-height: 32px;
        }

        /* Pastikan tidak ada leaflet-control / overlay liar yang menumpuk di atas kanvas peta.
           Semua leaflet-control WAJIB berada di dalam salah satu 4 pojok (top-left/top-right/bottom-left/bottom-right),
           tidak ada elemen absolute lain yang diletakkan manual di atas #map selain melalui Leaflet control API. */
        #map .leaflet-top,
        #map .leaflet-bottom {
            pointer-events: none;
        }
        #map .leaflet-control {
            pointer-events: auto;
        }

        /* Pulse Animation untuk Marker Event */
        .event-pulse { position: relative; }
        .event-pulse::after {
            content: '';
            position: absolute;
            top: -6px;
            left: -6px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.45);
            animation: pulse-ring 1.8s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
            z-index: -1;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.5); opacity: 1; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        /* Custom Scrollbar Sidebar (WebKit + Firefox) */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased overflow-hidden h-screen w-screen flex flex-col font-sans"
      x-data="petaFaskesApp()"
      x-init="initMap()">

    <!-- Header Navigation -->
    <header class="bg-white border-b border-slate-200 px-3 sm:px-4 pt-[max(0.625rem,env(safe-area-inset-top))] pb-2.5 sm:pb-3 flex flex-wrap items-center justify-between gap-x-3 gap-y-2 shadow-sm z-20 flex-shrink-0">
        <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
            <div class="w-9 h-9 sm:w-10 sm:h-10 flex-shrink-0 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-md shadow-blue-500/30">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <h1 class="text-sm sm:text-base md:text-lg font-bold text-slate-800 leading-tight truncate">SIG Pemetaan Faskes & Rute Darurat &bull; Kab. Banyumas</h1>
                <p class="hidden sm:block text-xs text-slate-500 truncate">Dinas Kesehatan Kabupaten Banyumas &bull; Analisis Spasial PostGIS & Routing OSRM</p>
            </div>
        </div>

        <!-- Legend Pills (Tablet & Desktop) -->
        <div class="hidden md:flex flex-wrap items-center gap-1.5 text-[11px] xl:text-xs">
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-red-50 text-red-700 font-medium border border-red-200">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 mr-1.5"></span> Rumah Sakit
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-medium border border-blue-200">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mr-1.5"></span> Puskesmas
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium border border-emerald-200">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5"></span> Klinik
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 font-medium border border-purple-200">
                <span class="w-2.5 h-2.5 rounded-full bg-purple-500 mr-1.5"></span> Lab
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 font-medium border border-amber-200">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-1.5"></span> UPKDK
            </span>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-1.5 sm:gap-2 ml-auto">
            <a href="{{ route('dashboard') }}" title="Dashboard"
               class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 transition focus:outline-none focus:ring-2 focus:ring-slate-300">
                <span>📊</span>
                <span class="hidden sm:inline">Dashboard</span>
            </a>
            <a href="{{ route('faskes.index') }}" title="Kelola Data Faskes"
               class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 transition focus:outline-none focus:ring-2 focus:ring-slate-300">
                <span>📋</span>
                <span class="hidden sm:inline">Kelola Data Faskes</span>
            </a>
            <button @click="sidebarOpen = !sidebarOpen"
                    class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 flex items-center gap-1.5 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                </svg>
                <span x-text="sidebarOpen ? 'Tutup Panel' : 'Panel Kontrol'"></span>
            </button>
        </div>
    </header>

    <!-- Main Content Area: Map & Sidebar -->
    <div class="flex-1 relative flex overflow-hidden">

        <!-- Leaflet Map Container (Fullscreen Clean Canvas) -->
        <div id="map" class="flex-1 h-full w-full z-0 cursor-crosshair"></div>

        <!-- Sidebar Kontrol: Filter Atribut & Analisis Lokasi Event -->
        <aside class="absolute lg:relative top-0 right-0 h-full w-full sm:w-[400px] lg:w-[440px] bg-white border-l border-slate-200 shadow-2xl z-20 flex flex-col transition-transform duration-300 ease-in-out"
               :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:hidden'">

            <!-- Sidebar Header & Tab Navigation -->
            <div class="p-3 border-b border-slate-200 bg-slate-50/90 flex-shrink-0 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                            🗺️
                        </span>
                        <h2 class="text-sm font-bold text-slate-800">Panel Kontrol Spasial</h2>
                    </div>
                    <button @click="sidebarOpen = false"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-md lg:hidden focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Tabs: Filter Atribut vs Analisis Event -->
                <div class="grid grid-cols-2 p-1 bg-slate-200/80 rounded-xl text-xs font-semibold gap-1">
                    <button type="button"
                            @click="activeTab = 'filter'"
                            :class="activeTab === 'filter' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="py-2 px-2 sm:px-3 rounded-lg transition flex items-center justify-center gap-1 sm:gap-1.5 focus:outline-none">
                        <span>🎛️</span>
                        <span class="truncate">Filter Atribut</span>
                        <span class="ml-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0"
                              :class="activeFilterCount() > 0 ? 'bg-blue-600 text-white' : 'bg-slate-300 text-slate-700'"
                              x-text="faskesList.length"></span>
                    </button>
                    <button type="button"
                            @click="activeTab = 'event'"
                            :class="activeTab === 'event' ? 'bg-white text-red-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="py-2 px-2 sm:px-3 rounded-lg transition flex items-center justify-center gap-1 sm:gap-1.5 focus:outline-none">
                        <span>📍</span>
                        <span class="truncate">Analisis Event</span>
                        <template x-if="hasilAnalisis && hasilAnalisis.length > 0">
                            <span class="ml-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-600 text-white flex-shrink-0"
                                  x-text="hasilAnalisis.length"></span>
                        </template>
                    </button>
                </div>
            </div>

            <!-- TAB 1: FILTER ATRIBUT DINAMIS (LIVE UPDATE MARKER) -->
            <div x-show="activeTab === 'filter'" class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-4 custom-scrollbar">

                <!-- Header Status Filter -->
                <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div>
                        <span class="font-bold text-blue-900">Marker Faskes Aktif</span>
                        <p class="text-blue-700 text-[11px]">
                            Menampilkan <b x-text="faskesList.length"></b> dari <span x-text="totalFaskes"></span> faskes
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <template x-if="isFiltering">
                            <div class="flex items-center gap-1 text-blue-600 font-semibold text-[11px]">
                                <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Menyaring...</span>
                            </div>
                        </template>
                        <button type="button" @click="resetDynamicFilters()"
                                class="text-[11px] px-2 py-1 rounded bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 font-medium transition focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Reset Filter
                        </button>
                    </div>
                </div>

                <!-- 1. Pilihan Jenis Faskes (Checkbox) -->
                <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-sm space-y-2.5">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <span>🏥</span>
                            <span>Pilihan Jenis Faskes</span>
                        </label>
                        <div class="space-x-1 text-[11px] flex-shrink-0">
                            <button type="button" @click="selectAllJenis()" class="text-blue-600 hover:text-blue-800 font-medium">Pilih Semua</button>
                            <span class="text-slate-300">&bull;</span>
                            <button type="button" @click="clearAllJenis()" class="text-slate-500 hover:text-slate-700 font-medium">Kosongkan</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 min-[380px]:grid-cols-2 gap-2 text-xs">
                        <!-- Rumah Sakit -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="rumah_sakit" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-red-600 focus:ring-red-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">Rumah Sakit</span>
                        </label>

                        <!-- Puskesmas -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="puskesmas" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">Puskesmas</span>
                        </label>

                        <!-- Klinik Pratama -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="klinik_pratama" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">Klinik Pratama</span>
                        </label>

                        <!-- Klinik Utama -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="klinik_utama" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-teal-600 focus:ring-teal-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">Klinik Utama</span>
                        </label>

                        <!-- Laboratorium -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="laboratorium" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-purple-600 focus:ring-purple-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">Laboratorium</span>
                        </label>

                        <!-- UPKDK -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="upkdk" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-amber-600 focus:ring-amber-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">UPKDK (Pustu/PKD)</span>
                        </label>
                    </div>
                </div>

                <!-- 2. Filter Spesifik (Atribut Child Tables via Eloquent whereHas) -->
                <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-sm space-y-2.5">
                    <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-2">
                        <span>⚡</span>
                        <span>Filter Spesifik Layanan & Fasilitas</span>
                    </label>

                    <div class="space-y-2 text-xs">
                        <!-- Ambulans Gadar / Transport -->
                        <label class="flex items-center justify-between gap-2 p-2 rounded-lg border border-slate-200 hover:bg-amber-50/50 cursor-pointer transition"
                               :class="filterAmbulans ? 'bg-amber-50 border-amber-300' : 'bg-white'">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="text-base flex-shrink-0">🚑</span>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800">Memiliki Ambulans Gadar / Transport</div>
                                    <div class="text-[10px] text-slate-500">RS, Puskesmas, atau Klinik yang siaga ambulans</div>
                                </div>
                            </div>
                            <input type="checkbox" x-model="filterAmbulans" @change="applyDynamicFilters()"
                                   class="h-4 w-4 rounded text-amber-600 focus:ring-amber-500 flex-shrink-0">
                        </label>

                        <!-- Melayani BPJS -->
                        <label class="flex items-center justify-between gap-2 p-2 rounded-lg border border-slate-200 hover:bg-emerald-50/50 cursor-pointer transition"
                               :class="filterBpjs ? 'bg-emerald-50 border-emerald-300' : 'bg-white'">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="text-base flex-shrink-0">💳</span>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800">Melayani Pasien BPJS Kesehatan</div>
                                    <div class="text-[10px] text-slate-500">Puskesmas, RS, dan Klinik Pratama mitra BPJS</div>
                                </div>
                            </div>
                            <input type="checkbox" x-model="filterBpjs" @change="applyDynamicFilters()"
                                   class="h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500 flex-shrink-0">
                        </label>

                        <!-- Memiliki Bed Rawat Inap -->
                        <label class="flex items-center justify-between gap-2 p-2 rounded-lg border border-slate-200 hover:bg-blue-50/50 cursor-pointer transition"
                               :class="filterRawatInap ? 'bg-blue-50 border-blue-300' : 'bg-white'">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="text-base flex-shrink-0">🛏️</span>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800">Memiliki Bed Rawat Inap</div>
                                    <div class="text-[10px] text-slate-500">RS, Puskesmas Rawat Inap, atau Klinik berbed</div>
                                </div>
                            </div>
                            <input type="checkbox" x-model="filterRawatInap" @change="applyDynamicFilters()"
                                   class="h-4 w-4 rounded text-blue-600 focus:ring-blue-500 flex-shrink-0">
                        </label>

                        <!-- Status PONED (Khusus Puskesmas) -->
                        <label class="flex items-center justify-between gap-2 p-2 rounded-lg border border-slate-200 hover:bg-rose-50/50 cursor-pointer transition"
                               :class="filterPoned ? 'bg-rose-50 border-rose-300' : 'bg-white'">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="text-base flex-shrink-0">👶</span>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800">Status PONED (Khusus Puskesmas)</div>
                                    <div class="text-[10px] text-slate-500">Pelayanan Obstetri Neonatal Emergensi Dasar</div>
                                </div>
                            </div>
                            <input type="checkbox" x-model="filterPoned" @change="applyDynamicFilters()"
                                   class="h-4 w-4 rounded text-rose-600 focus:ring-rose-500 flex-shrink-0">
                        </label>
                    </div>
                </div>

                <!-- Layer Batas Wilayah Kabupaten (Garis Pembatas Non-Interaktif) -->
                <div class="bg-white rounded-xl p-3 border border-slate-200 shadow-sm flex items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-base flex-shrink-0">🗺️</span>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-800">Layer Batas Kabupaten</div>
                            <div class="text-[10px] text-slate-500">Garis batas luar Kab. Banyumas saja (tanpa batas kecamatan)</div>
                        </div>
                    </div>
                    <button type="button"
                            @click="toggleKabupatenLayer()"
                            :class="showKabupatenLayer ? 'bg-blue-600' : 'bg-slate-300'"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            role="switch"
                            :aria-checked="showKabupatenLayer"
                            title="Nyalakan / Matikan Garis Batas Kabupaten">
                        <span :class="showKabupatenLayer ? 'translate-x-4' : 'translate-x-0'"
                              class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </div>

                <!-- 3. Filter Wilayah Kecamatan (Dropdown) & Pencarian Teks -->
                <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-sm space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <span>📍</span>
                            <span>Filter Wilayah Kecamatan</span>
                        </label>
                        <template x-if="selectedKecamatan">
                            <button type="button" @click="selectedKecamatan = ''; applyDynamicFilters(true)"
                                    class="text-[11px] text-blue-600 hover:text-blue-800 font-semibold flex-shrink-0">
                                Reset Wilayah
                            </button>
                        </template>
                    </div>

                    <!-- Dropdown Pilihan Kecamatan -->
                    <div>
                        <label class="block text-[11px] text-slate-500 mb-1">Pilih Kecamatan:</label>
                        <select x-model="selectedKecamatan" @change="applyDynamicFilters(true)"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                            <option value="">-- Semua Kecamatan (Kab. Banyumas) --</option>
                            <template x-for="kec in daftarKecamatan" :key="kec">
                                <option :value="kec" x-text="`Kecamatan ${kec}`"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Input Pencarian Teks -->
                    <div>
                        <label class="block text-[11px] text-slate-500 mb-1">Pencarian Teks / Nama:</label>
                        <div class="relative">
                            <input type="text" x-model="filterSearch" @input.debounce.300ms="applyDynamicFilters(false)"
                                   placeholder="Cari nama faskes, alamat, desa..."
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-700 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <template x-if="filterSearch">
                                <button @click="filterSearch = ''; applyDynamicFilters(false)"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold text-xs">&times;</button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 4. Daftar Faskes Hasil Filter (Dapat Diklik untuk Fokus ke Peta) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Daftar Faskes Terpilih</h3>
                        <span class="text-[11px] text-slate-500 flex-shrink-0" x-text="`${faskesList.length} Item`"></span>
                    </div>

                    <div class="space-y-2">
                        <template x-for="faskes in faskesList" :key="faskes.id">
                            <div @click="zoomToFaskes(faskes)"
                                 class="p-2.5 rounded-lg border border-slate-200 bg-white hover:border-blue-300 hover:shadow-sm cursor-pointer transition flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 mb-0.5">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0"
                                              :class="{
                                                  'bg-red-500': faskes.jenis_faskes === 'rumah_sakit',
                                                  'bg-blue-500': faskes.jenis_faskes === 'puskesmas',
                                                  'bg-emerald-500': faskes.jenis_faskes === 'klinik_pratama',
                                                  'bg-teal-500': faskes.jenis_faskes === 'klinik_utama',
                                                  'bg-purple-500': faskes.jenis_faskes === 'laboratorium',
                                                  'bg-amber-500': faskes.jenis_faskes === 'upkdk'
                                              }"></span>
                                        <span class="text-[10px] font-bold uppercase text-slate-500 truncate" x-text="formatJenis(faskes.jenis_faskes)"></span>
                                    </div>
                                    <div class="font-bold text-xs text-slate-900 leading-tight truncate" x-text="faskes.nama"></div>
                                    <div class="text-[11px] text-slate-500 truncate" x-text="faskes.alamat || `Kec. ${faskes.kecamatan || '-'}`"></div>
                                </div>
                                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                    <template x-if="faskes.detail && ((parseInt(faskes.detail.ambulans_transport) || 0) + (parseInt(faskes.detail.ambulans_roda_dua) || 0) + (parseInt(faskes.detail.ambulans_gadar) || 0) + (parseInt(faskes.detail.ambulans) || 0)) > 0">
                                        <span class="px-1.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded text-[9px] font-bold whitespace-nowrap"
                                              x-text="`🚑 ${((parseInt(faskes.detail.ambulans_transport) || 0) + (parseInt(faskes.detail.ambulans_roda_dua) || 0) + (parseInt(faskes.detail.ambulans_gadar) || 0) + (parseInt(faskes.detail.ambulans) || 0))} Amb`"></span>
                                    </template>
                                    <template x-if="faskes.detail && faskes.detail.poned === 'Ya PONED'">
                                        <span class="px-1.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded text-[9px] font-bold whitespace-nowrap">PONED</span>
                                    </template>
                                    <template x-if="faskes.detail && faskes.detail.ponek === 'Ya PONEK'">
                                        <span class="px-1.5 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded text-[9px] font-bold whitespace-nowrap">PONEK</span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="faskesList.length === 0">
                            <div class="p-6 text-center border border-dashed border-slate-200 rounded-xl bg-white text-slate-400 text-xs">
                                <span class="text-2xl block mb-1">🔍</span>
                                <p class="font-bold">Tidak ada faskes cocok</p>
                                <p class="mt-0.5 text-slate-500">Coba ubah kombinasi filter atau jenis faskes.</p>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- TAB 2: ANALISIS LOKASI EVENT (POSTGIS & OSRM) -->
            <div x-show="activeTab === 'event'" class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-4 custom-scrollbar">

                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Titik Koordinat Event</label>
                        <template x-if="mapLockedNotice">
    <div class="bg-slate-800 text-white text-[11px] px-3 py-2 rounded-lg mb-2 flex items-center gap-1.5">
        <span>🔒</span>
        <span>Titik terkunci — hapus dulu untuk memindahkan</span>
    </div>
</template>
                        <template x-if="eventLat && eventLng">
                            <div class="flex items-center justify-between gap-2 bg-white px-3 py-2 rounded-lg border border-slate-200 text-xs font-mono">
                                <span class="text-slate-700 truncate" x-text="`${eventLat.toFixed(6)}, ${eventLng.toFixed(6)}`"></span>
                                <button @click="resetEvent()" class="text-red-600 hover:text-red-700 text-xs font-sans font-semibold flex-shrink-0">Hapus</button>
                            </div>
                        </template>
                        <template x-if="!eventLat || !eventLng">
                            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs px-3 py-2 rounded-lg flex items-center gap-2">
                                <span>⚠️</span>
                                <span>Klik pada peta atau tombol di bawah untuk set titik event.</span>
                            </div>
                        </template>
                        <button @click="getCurrentLocation()"
                                class="mt-2 w-full py-1.5 px-3 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 transition focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <span>🎯</span>
                            <span>Gunakan Lokasi GPS Saya</span>
                        </button>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-xs font-semibold text-slate-700">Radius Pencarian</label>
                            <span class="text-xs font-bold text-blue-600" x-text="`${radiusKm} KM`"></span>
                        </div>
                        <input type="range" min="1" max="25" step="0.5" x-model.number="radiusKm" @input="updateRadiusCircle()"
                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">

                        <!-- Quick Presets -->
                        <div class="flex items-center justify-between mt-2 gap-1 text-[11px]">
                            <template x-for="r in [2, 5, 10, 15, 20]" :key="r">
                                <button type="button"
                                        @click="radiusKm = r; updateRadiusCircle()"
                                        :class="radiusKm === r ? 'bg-blue-600 text-white font-semibold' : 'bg-white text-slate-600 hover:bg-slate-200'"
                                        class="flex-1 py-1 rounded border border-slate-200 transition text-center focus:outline-none"
                                        x-text="`${r}km`">
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Tombol Cari Faskes Terdekat -->
                    <button @click="cariFaskesTerdekat()"
                            :disabled="!eventLat || !eventLng || isLoading"
                            :class="(!eventLat || !eventLng || isLoading) ? 'bg-slate-300 cursor-not-allowed text-slate-500' : 'bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20'"
                            class="w-full py-2.5 rounded-xl font-semibold text-xs flex items-center justify-center gap-2 transition focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
                        <template x-if="isLoading">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <template x-if="!isLoading">
                            <span>🚀</span>
                        </template>
                        <span x-text="isLoading ? 'Menghitung Rute PostGIS & OSRM...' : 'Cari Faskes Terdekat'"></span>
                    </button>
                </div>

                <!-- Hasil Analisis Event -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Hasil Faskes & Rute OSRM</h3>
                        <template x-if="hasilAnalisis && hasilAnalisis.length > 0">
                            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200 flex-shrink-0"
                                  x-text="`${hasilAnalisis.length} Ditemukan`"></span>
                        </template>
                    </div>

                    <!-- Kosong / Belum Dicari -->
                    <template x-if="!hasSearched">
                        <div class="text-center py-8 text-slate-400 border border-dashed border-slate-200 rounded-xl bg-white">
                            <span class="text-3xl block mb-2">🗺️</span>
                            <p class="text-xs px-4">Klik titik event di peta, lalu tekan <b>Cari Faskes Terdekat</b>.</p>
                        </div>
                    </template>

                    <!-- Ditemukan 0 -->
                    <template x-if="hasSearched && (!hasilAnalisis || hasilAnalisis.length === 0)">
                        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-center text-xs">
                            <p class="font-bold">Tidak ada faskes ditemukan!</p>
                            <p class="mt-1 text-red-600">Coba perbesar radius pencarian (misal 10 KM atau 15 KM).</p>
                        </div>
                    </template>

                    <!-- Daftar Kartu Faskes Terdekat & Tombol Export -->
                    <div class="space-y-3" x-show="hasilAnalisis && hasilAnalisis.length > 0">
                        <!-- Export Hasil Analisis Buttons -->
                        <div class="flex items-center gap-2 pb-1">
                            <a :href="getExportUrl('excel')" target="_blank"
                               class="flex-1 py-1.5 px-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                                <span>📊</span>
                                <span>Export Excel</span>
                            </a>
                            <a :href="getExportUrl('pdf')" target="_blank"
                               class="flex-1 py-1.5 px-3 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                                <span>📄</span>
                                <span>Export PDF</span>
                            </a>
                        </div>

                        <template x-for="(item, index) in hasilAnalisis" :key="item.detail_faskes.id">
                            <div @click="fokusKeRute(item, index)"
                                 :class="activeFaskesId === item.detail_faskes.id ? 'ring-2 ring-blue-500 bg-blue-50/40' : 'bg-white hover:border-slate-300'"
                                 class="p-3.5 rounded-xl border border-slate-200 shadow-sm cursor-pointer transition flex flex-col gap-2.5">

                                <!-- Card Header -->
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center flex-wrap gap-1.5 mb-1">
                                            <template x-if="index === 0">
                                                <span class="text-[10px] uppercase font-black px-1.5 py-0.5 rounded bg-emerald-500 text-white whitespace-nowrap">
                                                    ★ Rute Terbaik
                                                </span>
                                            </template>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap"
                                                  :class="{
                                                      'bg-red-100 text-red-700': item.detail_faskes.jenis_faskes === 'rumah_sakit',
                                                      'bg-blue-100 text-blue-700': item.detail_faskes.jenis_faskes === 'puskesmas',
                                                      'bg-emerald-100 text-emerald-700': item.detail_faskes.jenis_faskes === 'klinik_pratama',
                                                      'bg-teal-100 text-teal-700': item.detail_faskes.jenis_faskes === 'klinik_utama',
                                                      'bg-purple-100 text-purple-700': item.detail_faskes.jenis_faskes === 'laboratorium',
                                                      'bg-amber-100 text-amber-800': item.detail_faskes.jenis_faskes === 'upkdk'
                                                  }"
                                                  x-text="formatJenis(item.detail_faskes.jenis_faskes)">
                                            </span>
                                        </div>
                                        <h4 class="font-bold text-slate-800 text-xs leading-tight truncate" x-text="item.detail_faskes.nama"></h4>
                                        <p class="text-[11px] text-slate-500 mt-0.5 truncate" x-text="item.detail_faskes.alamat || '-'"></p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <template x-if="item.rute_tersedia">
                                            <div class="text-sm font-extrabold text-blue-600 leading-none whitespace-nowrap"
                                                 x-text="`${item.estimasi_waktu.menit.toFixed(1)} mnt`"></div>
                                        </template>
                                        <template x-if="!item.rute_tersedia">
                                            <div class="text-xs font-bold text-red-500 leading-none whitespace-nowrap">Rute N/A</div>
                                        </template>
                                        <div class="text-[10px] text-slate-500 mt-0.5 font-medium whitespace-nowrap">Estimasi Waktu</div>
                                    </div>
                                </div>

                                <!-- Peringatan kalau OSRM gagal menghitung rute jalan untuk faskes ini -->
                                <template x-if="!item.rute_tersedia">
                                    <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-2.5 py-1.5 text-[10px] flex items-start gap-1.5">
                                        <span class="flex-shrink-0">⚠️</span>
                                        <span x-text="item.pesan_rute || 'Rute jalan tidak dapat dihitung untuk faskes ini. Jarak lurus tetap akurat, tapi estimasi jalan/waktu tidak tersedia.'"></span>
                                    </div>
                                </template>

                                <!-- Card Metrics: Jarak Lurus vs Jarak Jalan -->
                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                                    <div class="bg-slate-50 rounded-lg p-2 min-w-0">
                                        <span class="text-[10px] text-slate-500 block font-medium">Jarak Lurus (PostGIS)</span>
                                        <span class="font-bold text-slate-700" x-text="`${item.jarak_lurus.km.toFixed(2)} KM`"></span>
                                    </div>
                                    <div class="bg-slate-50 rounded-lg p-2 min-w-0">
                                        <span class="text-[10px] text-slate-500 block font-medium">Jarak Jalan (OSRM)</span>
                                        <span class="font-bold text-slate-900"
                                              x-text="item.rute_tersedia ? `${item.jarak_jalan.km.toFixed(2)} KM` : '-'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Footer Stats -->
            <div class="p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] bg-slate-50 border-t border-slate-200 text-center text-[11px] text-slate-500 flex-shrink-0">
                Total Master Data: <span class="font-bold text-slate-700" x-text="totalFaskes"></span> Faskes &bull; Kab. Banyumas
            </div>
        </aside>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function petaFaskesApp() {
            return {
                map: null,
                sidebarOpen: true,
                activeTab: 'filter', // 'filter' atau 'event'
                isLoading: false,
                isFiltering: false,
                hasSearched: false,
                totalFaskes: 0,

                // Filter Atribut Dinamis
                selectedJenis: ['rumah_sakit', 'puskesmas', 'klinik_pratama', 'klinik_utama', 'laboratorium', 'upkdk'],
                filterAmbulans: false,
                filterBpjs: false,
                filterRawatInap: false,
                filterPoned: false,
                filterSearch: '',

                // State Layer Batas Wilayah Kabupaten & Pilihan Wilayah
                showKabupatenLayer: true,
                selectedKecamatan: '',
                daftarKecamatan: [
                    'Ajibarang', 'Banyumas', 'Baturraden', 'Cilongok', 'Gumelar',
                    'Jatilawang', 'Kalibagor', 'Karanglewas', 'Kebasen', 'Kedungbanteng',
                    'Kembaran', 'Kemranjen', 'Lumbir', 'Patikraja', 'Pekuncen',
                    'Purwojati', 'Purwokerto Barat', 'Purwokerto Selatan', 'Purwokerto Timur', 'Purwokerto Utara',
                    'Rawalo', 'Sokaraja', 'Somagede', 'Sumbang', 'Sumpiuh',
                    'Tambak', 'Wangon'
                ],

                // Parameter Event
                eventLat: null,
                eventLng: null,
                radiusKm: 5.0,
                activeFaskesId: null,

                // Layer Groups Leaflet
                kabupatenLayerGroup: null,
                kabupatenGeoJsonLayer: null,
                faskesLayerGroup: null,
                eventLayerGroup: null,
                routeLayerGroup: null,
                radiusCircle: null,
                eventMarker: null,
                markerMap: {},

                // Flag internal untuk mencegah render marker bertumpuk saat animasi peta masih berjalan
                _pendingRender: null,

                // Data Hasil
                faskesList: [],
                hasilAnalisis: [],

                initMap() {
                    // Default center: Purwokerto, Kabupaten Banyumas (-7.424364, 109.230345)
                    this.map = L.map('map', {
                        zoomControl: false,
                        markerZoomAnimation: true,
                        fadeAnimation: true
                    }).setView([-7.424364, 109.230345], 12);

                    // Posisi Zoom Control di pojok kiri bawah
                    L.control.zoom({ position: 'bottomleft' }).addTo(this.map);

                    // Base Layer OpenStreetMap
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(this.map);

                    // Layer Groups Leaflet:
                    // 1. Batas Kabupaten (Garis pembatas non-interaktif)
                    // 2. Faskes Markers
                    // 3. Rute OSRM
                    // 4. Titik Event & Radius Circle
                    this.kabupatenLayerGroup = L.layerGroup().addTo(this.map);
                    this.faskesLayerGroup = L.layerGroup().addTo(this.map);
                    this.routeLayerGroup = L.layerGroup().addTo(this.map);
                    this.eventLayerGroup = L.layerGroup().addTo(this.map);

                    // Muat Batas Luar Kabupaten Banyumas (Non-Interaktif)
                    this.loadKabupatenBoundary();

                    // Listener Klik Peta untuk menentukan Titik Event
                  this.map.on('click', (e) => {
    if (this.eventLat !== null && this.eventLng !== null) {
        this.mapLockedNotice = true;
        setTimeout(() => { this.mapLockedNotice = false; }, 1800);
        return;
    }
    this.setEventPoint(e.latlng.lat, e.latlng.lng);
});

                    // Muat Semua Data Faskes ke Peta
                    this.loadInitialFaskes();

                    this.map.on('click', (e) => {
    // FIX: kalau titik event sudah ada, abaikan klik peta lain —
    // harus dihapus dulu dari panel (Tab Analisis Event > tombol "Hapus")
    // supaya titik nggak ketuker/pindah gara-gara kepencet nggak sengaja.
    if (this.eventLat !== null && this.eventLng !== null) {
        return;
    }
    this.setEventPoint(e.latlng.lat, e.latlng.lng);
});
                    // Listener Kustom untuk 'Jadikan Titik Event' dari Popup Faskes
                    window.addEventListener('set-event-here', (e) => {
    if (this.eventLat !== null && this.eventLng !== null) return; // opsional: kunci juga di sini
    this.setEventPoint(e.detail.lat, e.detail.lng);
    this.activeTab = 'event';
});

                    // Bersihkan floating overlay/badge liar yang mungkin tertinggal di atas
                    // kanvas peta dari inisialisasi sebelumnya. Hanya elemen di luar 4 pojok leaflet yang dihapus.
                    this.cleanupStrayMapOverlays();
                },

                // Hapus elemen apapun yang menempel langsung di #map tapi bukan bagian dari
                // struktur resmi Leaflet (leaflet-pane, leaflet-control-container, dsb).
                cleanupStrayMapOverlays() {
                    const mapEl = document.getElementById('map');
                    if (!mapEl) return;
                    const allowedClasses = ['leaflet-pane', 'leaflet-control-container', 'leaflet-zoom-box', 'leaflet-tile-container'];
                    Array.from(mapEl.children).forEach((child) => {
                        const isAllowed = allowedClasses.some(cls => child.classList.contains(cls));
                        if (!isAllowed) {
                            child.remove();
                        }
                    });
                },

                // Menghitung jumlah filter aktif
                activeFilterCount() {
                    let count = 0;
                    if (this.selectedJenis.length < 6) count++;
                    if (this.filterAmbulans) count++;
                    if (this.filterBpjs) count++;
                    if (this.filterRawatInap) count++;
                    if (this.filterPoned) count++;
                    if (this.selectedKecamatan) count++;
                    if (this.filterSearch.trim()) count++;
                    return count;
                },

                // Muat Data Pertama Kali
                loadInitialFaskes() {
                    this.isFiltering = true;
                    fetch('{{ url("/api/faskes") }}?all=true')
                        .then(res => res.json())
                        .then(res => {
                            this.isFiltering = false;
                            if (!res.success) return;

                            this.faskesList = res.data;
                            this.totalFaskes = this.faskesList.length;
                            this.renderMarkers(this.faskesList, true);
                        })
                        .catch(err => {
                            this.isFiltering = false;
                            console.error('Error load initial faskes:', err);
                        });
                },

                // AJAX: Ambil data faskes tersaring dari endpoint API /api/faskes/filter
                applyDynamicFilters(fitBounds = false) {
                    this.isFiltering = true;

                    // Jika semua checkbox jenis tidak dicentang, kosongkan marker langsung
                    if (this.selectedJenis.length === 0) {
                        this.safeClearFaskesLayer();
                        this.faskesList = [];
                        this.isFiltering = false;
                        return;
                    }

                    const params = new URLSearchParams();

                    // Array jenis faskes
                    this.selectedJenis.forEach(j => params.append('jenis_faskes[]', j));

                    // Filter spesifik atribut
                    if (this.filterAmbulans) params.append('has_ambulans', '1');
                    if (this.filterBpjs) params.append('has_bpjs', '1');
                    if (this.filterRawatInap) params.append('has_rawat_inap', '1');
                    if (this.filterPoned) params.append('has_poned', '1');
                    if (this.selectedKecamatan) params.append('kecamatan', this.selectedKecamatan);
                    if (this.filterSearch.trim()) params.append('search', this.filterSearch.trim());

                    fetch(`{{ url("/api/faskes/filter") }}?${params.toString()}`)
                        .then(res => res.json())
                        .then(res => {
                            this.isFiltering = false;
                            if (!res.success) return;

                            this.faskesList = res.data;
                            this.renderMarkers(this.faskesList, fitBounds);
                        })
                        .catch(err => {
                            this.isFiltering = false;
                            console.error('Error filtering faskes:', err);
                        });
                },

                // Tutup semua popup & bersihkan layer group DULU secara aman
                // sebelum layer Leaflet lain (mis. animasi zoom) sempat mereferensikan marker yang sudah tak ada.
                safeClearFaskesLayer() {
                    if (!this.map) return;
                    try {
                        this.map.closePopup();
                    } catch (e) { /* no-op */ }
                    this.faskesLayerGroup.clearLayers();
                    this.markerMap = {};
                },

                // Render marker dengan lifecycle yang aman terhadap animasi Leaflet.
                renderMarkers(list, fitBounds = false) {
                    if (!this.map) return;

                    const doRender = () => {
                        this.safeClearFaskesLayer();
                        const bounds = [];

                        list.forEach(faskes => {
                            if (faskes.latitude === null || faskes.longitude === null ||
                                faskes.latitude === undefined || faskes.longitude === undefined) {
                                return;
                            }

                            // Parsing tegas + validasi finite number sebelum dipakai L.marker
                            const lat = parseFloat(faskes.latitude);
                            const lng = parseFloat(faskes.longitude);
                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                            bounds.push([lat, lng]);

                            const markerIcon = this.createFaskesIcon(faskes.jenis_faskes);
                            const marker = L.marker([lat, lng], { icon: markerIcon });

                            marker.bindPopup(this.createFaskesPopupHtml(faskes));
                            this.faskesLayerGroup.addLayer(marker);

                            this.markerMap[faskes.id] = marker;
                        });

                        if (fitBounds && bounds.length > 0) {
                            // animate:false agar kalkulasi posisi piksel tidak rusak
                            // saat clearLayers() + addLayer() terjadi hampir bersamaan dengan pan/zoom.
                            this.map.fitBounds(bounds, { padding: [40, 40], animate: false });
                        }
                    };

                    // Jika peta sedang dalam animasi zoom/pan, tunda render
                    // ke frame berikutnya agar tidak bentrok dengan proses reposisi Leaflet internal.
                    if (this.map._animatingZoom) {
                        this.map.once('zoomend', doRender);
                    } else {
                        doRender();
                    }
                },

                // Tutup popup sebelum memindahkan viewport, dan matikan animasi
                // supaya tidak tumpang tindih dengan render marker berikutnya.
                zoomToFaskes(faskes) {
                    if (!faskes.latitude || !faskes.longitude || !this.map) return;
                    const lat = parseFloat(faskes.latitude);
                    const lng = parseFloat(faskes.longitude);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                    this.map.closePopup();
                    this.map.setView([lat, lng], 15, { animate: false });

                    if (this.markerMap[faskes.id]) {
                        this.markerMap[faskes.id].openPopup();
                    }
                },

                selectAllJenis() {
                    this.selectedJenis = ['rumah_sakit', 'puskesmas', 'klinik_pratama', 'klinik_utama', 'laboratorium', 'upkdk'];
                    this.applyDynamicFilters();
                },

                clearAllJenis() {
                    this.selectedJenis = [];
                    this.applyDynamicFilters();
                },

                resetDynamicFilters() {
                    this.selectedJenis = ['rumah_sakit', 'puskesmas', 'klinik_pratama', 'klinik_utama', 'laboratorium', 'upkdk'];
                    this.filterAmbulans = false;
                    this.filterBpjs = false;
                    this.filterRawatInap = false;
                    this.filterPoned = false;
                    this.selectedKecamatan = '';
                    this.filterSearch = '';
                    this.applyDynamicFilters(true);
                },

                // Muat Batas Luar Kabupaten Banyumas (Outer Boundary Only & Non-Interaktif)
                loadKabupatenBoundary() {
                    fetch('{{ asset("geojson/banyumas-kabupaten-outer.json") }}')
                        .then(res => {
                            if (!res.ok) throw new Error('Gagal memuat boundary kabupaten: ' + res.status);
                            return res.json();
                        })
                        .then(geojsonData => {
                            this.kabupatenGeoJsonLayer = L.geoJSON(geojsonData, {
                                interactive: false,
                                bubblingMouseEvents: false,
                                style: {
                                    color: '#1d4ed8',
                                    weight: 2.5,
                                    fill: false,
                                    interactive: false
                                }
                            });

                            if (this.showKabupatenLayer) {
                                this.kabupatenLayerGroup.addLayer(this.kabupatenGeoJsonLayer);
                            }
                        })
                        .catch(err => {
                            console.error('Error load geojson kabupaten:', err);
                        });
                },

                // Toggle Switch Batas Kabupaten (Layer Control)
                toggleKabupatenLayer() {
                    this.showKabupatenLayer = !this.showKabupatenLayer;
                    if (this.showKabupatenLayer) {
                        if (this.kabupatenGeoJsonLayer) {
                            this.kabupatenLayerGroup.addLayer(this.kabupatenGeoJsonLayer);
                        }
                    } else {
                        this.kabupatenLayerGroup.clearLayers();
                    }
                },

                // Ambil Lokasi GPS User
                getCurrentLocation() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                const lat = pos.coords.latitude;
                                const lng = pos.coords.longitude;
                                this.setEventPoint(lat, lng);
                                this.map.setView([lat, lng], 14, { animate: false });
                            },
                            (err) => {
                                alert('Gagal membaca lokasi GPS: ' + err.message);
                            }
                        );
                    } else {
                        alert('Browser Anda tidak mendukung Geolocation.');
                    }
                },

                // Menetapkan Titik Event Baru
                setEventPoint(lat, lng) {
    if (!this.map) return;
    this.map.closePopup();

    this.eventLat = lat;
    this.eventLng = lng;
    this.sidebarOpen = true;
    this.activeTab = 'event';

    const popupHtml = `
        <div class="text-xs p-1">
            <b class="text-red-600">Titik Event / Kejadian</b><br>
            Lat: ${lat.toFixed(6)}<br>
            Lng: ${lng.toFixed(6)}<br>
            <span class="text-[10px] text-slate-400">Hapus titik di panel untuk memindahkan</span>
        </div>
    `;

    if (this.eventMarker) {
        // Reuse marker yang sudah ada — jangan clearLayers()+recreate (lihat fix sebelumnya)
        this.eventMarker.setLatLng([lat, lng]);
        this.eventMarker.setPopupContent(popupHtml);
    } else {
        const eventIcon = L.divIcon({
            className: 'event-pulse',
            html: `
                <div style="background-color: #ef4444; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(239,68,68,0.5); border: 2px solid #ffffff;">
                    <span style="transform: rotate(45deg); font-size: 15px; color: white;">📍</span>
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        // FIX: draggable dihapus. Titik event sekarang cuma bisa dipindah
        // dengan alur eksplisit: hapus dulu (resetEvent) lalu klik titik baru —
        // supaya nggak ada risiko kepencet/kegeser nggak sengaja.
        this.eventMarker = L.marker([lat, lng], {
            icon: eventIcon
        }).addTo(this.eventLayerGroup);

        this.eventMarker.bindPopup(popupHtml);
    }

    this.updateRadiusCircle();
},

                // Update Lingkaran Radius Pencarian
                updateRadiusCircle() {
                    if (!this.eventLat || !this.eventLng) return;

                    if (this.radiusCircle) {
                        this.eventLayerGroup.removeLayer(this.radiusCircle);
                    }

                    this.radiusCircle = L.circle([this.eventLat, this.eventLng], {
                        radius: this.radiusKm * 1000,
                        color: '#3b82f6',
                        weight: 2,
                        opacity: 0.8,
                        fillColor: '#60a5fa',
                        fillOpacity: 0.12,
                        dashArray: '6, 6'
                    }).addTo(this.eventLayerGroup);
                },

                // Reset Event
                resetEvent() {
                    if (this.map) this.map.closePopup();
                    this.eventLat = null;
                    this.eventLng = null;
                    this.eventLayerGroup.clearLayers();
                    this.routeLayerGroup.clearLayers();
                    this.hasilAnalisis = [];
                    this.hasSearched = false;
                    this.radiusCircle = null;
                    this.eventMarker = null;
                },

                // Jalankan Pencarian Faskes & Rute OSRM
                cariFaskesTerdekat() {
                    if (!this.eventLat || !this.eventLng) return;

                    this.isLoading = true;
                    this.routeLayerGroup.clearLayers();

                    const params = new URLSearchParams({
                        latitude: this.eventLat,
                        longitude: this.eventLng,
                        radius_km: this.radiusKm,
                        limit: 6,
                        sort_by: 'jarak_jalan'
                    });

                    // Sertakan filter aktif jenis & spesifik
                    if (this.selectedJenis.length > 0 && this.selectedJenis.length < 6) {
                        this.selectedJenis.forEach(j => params.append('jenis_faskes[]', j));
                    }
                    if (this.filterAmbulans) params.append('has_ambulans', '1');
                    if (this.filterBpjs) params.append('has_bpjs', '1');
                    if (this.filterRawatInap) params.append('has_rawat_inap', '1');
                    if (this.filterPoned) params.append('has_poned', '1');

                    // FIX BUG: pakai helper url() Laravel (bukan path hardcode) supaya tetap benar
                    // kalau aplikasi di-deploy di subfolder / base path selain root domain.
                    fetch(`{{ url("/api/analisis-event") }}?${params.toString()}`)
                        .then(res => res.json())
                        .then(res => {
                            this.isLoading = false;
                            this.hasSearched = true;

                            if (!res.success) {
                                alert('Gagal melakukan analisis faskes: ' + (res.message || 'Error server'));
                                return;
                            }

                            this.hasilAnalisis = res.data;
                            if (this.hasilAnalisis.length === 0) return;

                            const routeColors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#64748b'];

                            this.hasilAnalisis.forEach((item, index) => {
                                if (item.titik_rute_jalan) {
                                    const color = routeColors[index % routeColors.length];
                                    const isBest = index === 0;

                                    const routeLayer = L.geoJSON(item.titik_rute_jalan, {
                                        style: {
                                            color: color,
                                            weight: isBest ? 6 : 4,
                                            opacity: isBest ? 0.95 : 0.65,
                                            dashArray: isBest ? null : '6, 6'
                                        }
                                    }).addTo(this.routeLayerGroup);

                                    routeLayer.bindPopup(`
                                        <div class="text-xs p-1">
                                            <b>${item.detail_faskes.nama}</b><br>
                                            Jarak Jalan: <b>${item.jarak_jalan.km.toFixed(2)} km</b><br>
                                            Estimasi Waktu: <b>${item.estimasi_waktu.menit.toFixed(1)} menit</b>
                                        </div>
                                    `);
                                }
                            });

                            if (this.hasilAnalisis.length > 0) {
                                this.activeFaskesId = this.hasilAnalisis[0].detail_faskes.id;
                            }
                        })
                        .catch(err => {
                            this.isLoading = false;
                            console.error('Error analisis event:', err);
                            alert('Gagal menghubungi server API analisis event.');
                        });
                },

                // Fokus ke Salah Satu Rute Faskes
                fokusKeRute(item, index) {
                    if (!this.map) return;
                    this.map.closePopup();
                    this.activeFaskesId = item.detail_faskes.id;
                    const faskes = item.detail_faskes;

                    if (item.titik_rute_jalan) {
                        const tempLayer = L.geoJSON(item.titik_rute_jalan);
                        this.map.fitBounds(tempLayer.getBounds(), { padding: [60, 60], animate: false });
                    } else {
                        const lat = parseFloat(faskes.latitude);
                        const lng = parseFloat(faskes.longitude);
                        if (Number.isFinite(lat) && Number.isFinite(lng)) {
                            this.map.setView([lat, lng], 15, { animate: false });
                        }
                    }
                },

                // URL Helper untuk Export Hasil Analisis (Excel / PDF)
                getExportUrl(format) {
                    if (!this.eventLat || !this.eventLng) return '#';
                    const base = format === 'excel'
                        ? '{{ route("analisis.export.excel") }}'
                        : '{{ route("analisis.export.pdf") }}';
                    const params = new URLSearchParams({
                        latitude: this.eventLat,
                        longitude: this.eventLng,
                        radius_km: this.radiusKm,
                        limit: 5
                    });
                    if (this.selectedJenis.length > 0 && this.selectedJenis.length < 6) {
                        this.selectedJenis.forEach(j => params.append('jenis_faskes[]', j));
                    }
                    if (this.filterAmbulans) params.append('has_ambulans', '1');
                    if (this.filterBpjs) params.append('has_bpjs', '1');
                    if (this.filterRawatInap) params.append('has_rawat_inap', '1');
                    if (this.filterPoned) params.append('has_poned', '1');

                    return `${base}?${params.toString()}`;
                },

                // Generator Custom Leaflet Marker Icon Berdasarkan Jenis Faskes
                createFaskesIcon(jenis) {
                    const iconConfig = {
                        'rumah_sakit':    { color: '#ef4444', label: 'RS' },
                        'puskesmas':      { color: '#3b82f6', label: 'PKM' },
                        'klinik_pratama': { color: '#10b981', label: 'KL' },
                        'klinik_utama':   { color: '#14b8a6', label: 'KL' },
                        'laboratorium':   { color: '#8b5cf6', label: 'LAB' },
                        'upkdk':          { color: '#f59e0b', label: 'UPK' }
                    };

                    const cfg = iconConfig[jenis] || { color: '#64748b', label: 'F' };

                    return L.divIcon({
                        className: 'custom-faskes-marker',
                        html: `
                            <div style="background-color: ${cfg.color}; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3); border: 2px solid #ffffff;">
                                <span style="transform: rotate(45deg); color: white; font-size: 10px; font-weight: 800; letter-spacing: -0.5px;">${cfg.label}</span>
                            </div>
                        `,
                        iconSize: [32, 32],
                        iconAnchor: [16, 32],
                        popupAnchor: [0, -32]
                    });
                },

                // Popup HTML Faskes
                createFaskesPopupHtml(faskes) {
                    const jenisText = this.formatJenis(faskes.jenis_faskes);
                    const detail = faskes.detail || {};

                    let badgeHtml = '';
                    const totalAmbulans = (parseInt(detail.ambulans_transport) || 0) +
                                          (parseInt(detail.ambulans_roda_dua) || 0) +
                                          (parseInt(detail.ambulans_gadar) || 0) +
                                          (parseInt(detail.ambulans) || 0);

                    if (totalAmbulans > 0) {
                        badgeHtml += `<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 mr-1">🚑 ${totalAmbulans} Ambulans</span>`;
                    }
                    if (detail.poned === 'Ya PONED') {
                        badgeHtml += '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800 mr-1">👶 PONED</span>';
                    }
                    if (detail.ponek === 'Ya PONEK') {
                        badgeHtml += '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 text-purple-800 mr-1">🏥 PONEK</span>';
                    }
                    if (detail.mampu_salin === 'Ya') {
                        badgeHtml += '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-pink-100 text-pink-800 mr-1">Mampu Salin</span>';
                    }
                    if (detail.bpjs === true || detail.bpjs === 1 || detail.bpjs === 'Ya') {
                        badgeHtml += '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 mr-1">💳 BPJS</span>';
                    }
                    if (detail.jumlah_tempat_tidur > 0 || detail.bed_rawat_inap > 0) {
                        const bed = detail.jumlah_tempat_tidur || detail.bed_rawat_inap;
                        badgeHtml += `<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-800 mr-1">🛏️ ${bed} Bed</span>`;
                    }
                    if (detail.is_pustu === 'Ya') {
                        badgeHtml += '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 mr-1">Pustu</span>';
                    }
                    if (detail.is_pkd === 'Ya') {
                        badgeHtml += '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-orange-100 text-orange-800 mr-1">PKD</span>';
                    }

                    return `
                        <div class="text-xs p-1 max-w-[240px]">
                            <div class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 mb-1">
                                ${jenisText}
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm leading-tight">${faskes.nama}</h4>
                            <p class="text-slate-500 text-[11px] mt-1">${faskes.alamat || '-'}</p>
                            ${faskes.nomor_telepon ? `<p class="text-slate-600 text-[11px] mt-1">📞 ${faskes.nomor_telepon}</p>` : ''}
                            ${badgeHtml ? `<div class="mt-2 flex flex-wrap gap-1">${badgeHtml}</div>` : ''}
                            <hr class="my-2 border-slate-100">
                            <button onclick="window.dispatchEvent(new CustomEvent('set-event-here', {detail: {lat: ${faskes.latitude}, lng: ${faskes.longitude}}}))"
                                    class="w-full text-center py-1 rounded bg-blue-50 text-blue-600 hover:bg-blue-100 font-semibold text-[11px] transition">
                                📍 Jadikan Titik Event
                            </button>
                        </div>
                    `;
                },

                formatJenis(jenis) {
                    const map = {
                        'rumah_sakit': 'Rumah Sakit',
                        'puskesmas': 'Puskesmas',
                        'klinik_pratama': 'Klinik Pratama',
                        'klinik_utama': 'Klinik Utama',
                        'laboratorium': 'Laboratorium',
                        'upkdk': 'UPKDK'
                    };
                    return map[jenis] || jenis;
                }
            }
        }
    </script>
</body>
</html>