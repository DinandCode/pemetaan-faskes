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

        /* Pastikan tidak ada leaflet-control / overlay liar yang menumpuk di atas kanvas peta. */
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

        /* Ikon default: gaya outline, warna ikut currentColor */
        .icon { fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .icon-solid { fill: currentColor; stroke: none; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased overflow-hidden h-screen w-screen flex flex-col font-sans"
      x-data="petaFaskesApp()"
      x-init="initMap()">

    <!-- ============================================================ -->
    <!-- SPRITE IKON SVG (satu sumber untuk semua ikon di halaman ini) -->
    <!-- ============================================================ -->
    <svg class="hidden" aria-hidden="true">
        <defs>
            <symbol id="ic-map" viewBox="0 0 24 24">
                <path d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.159.69.159 1.006 0z"/>
            </symbol>
            <symbol id="ic-chart" viewBox="0 0 24 24">
                <path d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C6.5 20.496 6 21 5.375 21h-2.25A1.125 1.125 0 012 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </symbol>
            <symbol id="ic-clipboard" viewBox="0 0 24 24">
                <path d="M9.75 3.75h4.5a.75.75 0 01.75.75v.75a.75.75 0 01-.75.75h-4.5A.75.75 0 019 5.25V4.5a.75.75 0 01.75-.75zM8.25 4.5H6.75A1.5 1.5 0 005.25 6v13.5A1.5 1.5 0 006.75 21h10.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5h-1.5M9 12h6M9 15h6M9 18h4"/>
            </symbol>
            <symbol id="ic-pin" viewBox="0 0 24 24">
                <path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                <path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
            </symbol>
            <symbol id="ic-hospital" viewBox="0 0 24 24">
                <path d="M4.5 21V6.75A2.25 2.25 0 016.75 4.5h10.5a2.25 2.25 0 012.25 2.25V21M4.5 21h15M9 21v-3.75c0-.414.336-.75.75-.75h4.5c.414 0 .75.336.75.75V21"/>
                <path d="M12 7.5v3m-1.5-1.5h3"/>
            </symbol>
            <symbol id="ic-bolt" viewBox="0 0 24 24">
                <path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
            </symbol>
            <symbol id="ic-medical" viewBox="0 0 24 24">
                <path d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </symbol>
            <symbol id="ic-card" viewBox="0 0 24 24">
                <path d="M3.75 6.75h16.5a1.5 1.5 0 011.5 1.5v7.5a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-7.5a1.5 1.5 0 011.5-1.5z"/>
                <path d="M2.25 10.5h19.5M6 15h3"/>
            </symbol>
            <symbol id="ic-bed" viewBox="0 0 24 24">
                <path d="M3 18v-6a3 3 0 013-3h12a3 3 0 013 3v6M3 18h18M3 18v1.5M21 18v1.5M6 12V9.75A1.5 1.5 0 017.5 8.25h3A1.5 1.5 0 0112 9.75V12"/>
            </symbol>
            <symbol id="ic-heart" viewBox="0 0 24 24">
                <path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
            </symbol>
            <symbol id="ic-shield" viewBox="0 0 24 24">
                <path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </symbol>
            <symbol id="ic-search" viewBox="0 0 24 24">
                <path d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </symbol>
            <symbol id="ic-warning" viewBox="0 0 24 24">
                <path d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.652 3.622c.866-1.5 3.03-1.5 3.896 0l7.755 12.502zM12 15.75h.007v.008H12v-.008z"/>
            </symbol>
            <symbol id="ic-target" viewBox="0 0 24 24">
                <path d="M7.5 3.75H6A2.25 2.25 0 003.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0120.25 6v1.5m0 9V18A2.25 2.25 0 0118 20.25h-1.5m-9 0H6A2.25 2.25 0 013.75 18v-1.5M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </symbol>
            <symbol id="ic-send" viewBox="0 0 24 24">
                <path d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
            </symbol>
            <symbol id="ic-table" viewBox="0 0 24 24">
                <path d="M3.75 5.25h16.5v13.5H3.75zM3.75 10.5h16.5M3.75 15h16.5M9.75 5.25v13.5M15 5.25v13.5"/>
            </symbol>
            <symbol id="ic-doc" viewBox="0 0 24 24">
                <path d="M6.75 3.75h7.5l4.5 4.5v11.25a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V5.25a1.5 1.5 0 011.5-1.5z"/>
                <path d="M14.25 3.75v4.5h4.5M9 13.5h6M9 16.5h6"/>
            </symbol>
            <symbol id="ic-phone" viewBox="0 0 24 24">
                <path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
            </symbol>
            <symbol id="ic-star" viewBox="0 0 24 24">
                <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 21.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
            </symbol>
            <symbol id="ic-lock" viewBox="0 0 24 24">
                <path d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </symbol>
        </defs>
    </svg>

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
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 font-medium border border-cyan-200">
                <span class="w-2.5 h-2.5 rounded-full bg-cyan-500 mr-1.5"></span> Griya Sehat
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-sky-50 text-sky-700 font-medium border border-sky-200">
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500 mr-1.5"></span> TPM
            </span>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-1.5 sm:gap-2 ml-auto">
            <a href="{{ route('dashboard') }}" title="Dashboard"
               class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 transition focus:outline-none focus:ring-2 focus:ring-slate-300">
                <svg class="icon w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0"><use href="#ic-chart"/></svg>
                <span class="hidden sm:inline">Dashboard</span>
            </a>
            <a href="{{ route('faskes.index') }}" title="Kelola Data Faskes"
               class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 transition focus:outline-none focus:ring-2 focus:ring-slate-300">
                <svg class="icon w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0"><use href="#ic-clipboard"/></svg>
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
                        <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0">
                            <svg class="icon w-4 h-4"><use href="#ic-map"/></svg>
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
                        <svg class="icon w-3.5 h-3.5 flex-shrink-0"><use href="#ic-bolt"/></svg>
                        <span class="truncate">Filter Atribut</span>
                        <span class="ml-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0"
                              :class="activeFilterCount() > 0 ? 'bg-blue-600 text-white' : 'bg-slate-300 text-slate-700'"
                              x-text="faskesList.length"></span>
                    </button>
                    <button type="button"
                            @click="activeTab = 'event'"
                            :class="activeTab === 'event' ? 'bg-white text-red-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="py-2 px-2 sm:px-3 rounded-lg transition flex items-center justify-center gap-1 sm:gap-1.5 focus:outline-none">
                        <svg class="icon w-3.5 h-3.5 flex-shrink-0"><use href="#ic-pin"/></svg>
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
                            <svg class="icon w-4 h-4 text-slate-600"><use href="#ic-hospital"/></svg>
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
                            <span class="text-slate-700 font-medium truncate">UPKDK</span>
                        </label>

                        <!-- Griya Sehat -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="griya_sehat" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-cyan-600 focus:ring-cyan-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">Griya Sehat</span>
                        </label>

                        <!-- TPMD -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="tpmd" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-sky-600 focus:ring-sky-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">TPMD (Dokter)</span>
                        </label>

                        <!-- TPMDG -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="tpmdg" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">TPMDG (Dokter Gigi)</span>
                        </label>

                        <!-- TPMB -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="tpmb" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-pink-600 focus:ring-pink-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-pink-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">TPMB (Bidan)</span>
                        </label>

                        <!-- TPMP -->
                        <label class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 border border-slate-100 cursor-pointer transition">
                            <input type="checkbox" value="tpmp" x-model="selectedJenis" @change="applyDynamicFilters()"
                                   class="rounded text-lime-600 focus:ring-lime-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-lime-500 flex-shrink-0"></span>
                            <span class="text-slate-700 font-medium truncate">TPMP (Perawat)</span>
                        </label>
                    </div>
                </div>

                <!-- 2. Filter Spesifik (Atribut Child Tables via Eloquent whereHas) -->
                <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-sm space-y-2.5">
                    <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5 border-b border-slate-100 pb-2">
                        <svg class="icon w-4 h-4 text-slate-600"><use href="#ic-bolt"/></svg>
                        <span>Filter Spesifik Layanan & Fasilitas</span>
                    </label>

                    <div class="space-y-2 text-xs">
                        <!-- Ambulans Gadar / Transport -->
                        <label class="flex items-center justify-between gap-2 p-2 rounded-lg border border-slate-200 hover:bg-amber-50/50 cursor-pointer transition"
                               :class="filterAmbulans ? 'bg-amber-50 border-amber-300' : 'bg-white'">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="icon w-5 h-5 text-amber-600 flex-shrink-0"><use href="#ic-medical"/></svg>
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
                                <svg class="icon w-5 h-5 text-emerald-600 flex-shrink-0"><use href="#ic-card"/></svg>
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
                                <svg class="icon w-5 h-5 text-blue-600 flex-shrink-0"><use href="#ic-bed"/></svg>
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
                                <svg class="icon w-5 h-5 text-rose-600 flex-shrink-0"><use href="#ic-heart"/></svg>
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
                        <svg class="icon w-5 h-5 text-slate-600 flex-shrink-0"><use href="#ic-map"/></svg>
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
                            <svg class="icon w-4 h-4 text-slate-600"><use href="#ic-pin"/></svg>
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
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded text-[9px] font-bold whitespace-nowrap">
                                            <svg class="icon w-2.5 h-2.5 flex-shrink-0"><use href="#ic-medical"/></svg>
                                            <span x-text="`${((parseInt(faskes.detail.ambulans_transport) || 0) + (parseInt(faskes.detail.ambulans_roda_dua) || 0) + (parseInt(faskes.detail.ambulans_gadar) || 0) + (parseInt(faskes.detail.ambulans) || 0))} Amb`"></span>
                                        </span>
                                    </template>
                                    <template x-if="faskes.detail && faskes.detail.poned === 'Ya PONED'">
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded text-[9px] font-bold whitespace-nowrap">
                                            <svg class="icon w-2.5 h-2.5 flex-shrink-0"><use href="#ic-heart"/></svg>
                                            <span>PONED</span>
                                        </span>
                                    </template>
                                    <template x-if="faskes.detail && faskes.detail.ponek === 'Ya PONEK'">
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded text-[9px] font-bold whitespace-nowrap">
                                            <svg class="icon w-2.5 h-2.5 flex-shrink-0"><use href="#ic-shield"/></svg>
                                            <span>PONEK</span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="faskesList.length === 0">
                            <div class="p-6 text-center border border-dashed border-slate-200 rounded-xl bg-white text-slate-400 text-xs">
                                <svg class="icon w-8 h-8 mx-auto mb-1 text-slate-300"><use href="#ic-search"/></svg>
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

                        <!-- Notifikasi sementara: titik terkunci, harus dihapus dulu untuk memindahkan -->
                        <template x-if="mapLockedNotice">
                            <div class="bg-slate-800 text-white text-[11px] px-3 py-2 rounded-lg mb-2 flex items-center gap-1.5">
                                <svg class="icon w-3.5 h-3.5 flex-shrink-0"><use href="#ic-lock"/></svg>
                                <span>Titik terkunci &mdash; hapus dulu untuk memindahkan</span>
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
                                <svg class="icon w-4 h-4 flex-shrink-0"><use href="#ic-warning"/></svg>
                                <span>Klik pada peta atau tombol di bawah untuk set titik event.</span>
                            </div>
                        </template>
                        <button @click="getCurrentLocation()"
                                class="mt-2 w-full py-1.5 px-3 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs font-medium flex items-center justify-center gap-1.5 transition focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <svg class="icon w-4 h-4"><use href="#ic-target"/></svg>
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
                            <svg class="icon w-4 h-4"><use href="#ic-send"/></svg>
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
                            <svg class="icon w-10 h-10 mx-auto mb-2 text-slate-300"><use href="#ic-map"/></svg>
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
                                <svg class="icon w-4 h-4"><use href="#ic-table"/></svg>
                                <span>Export Excel</span>
                            </a>
                            <a :href="getExportUrl('pdf')" target="_blank"
                               class="flex-1 py-1.5 px-3 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                                <svg class="icon w-4 h-4"><use href="#ic-doc"/></svg>
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
                                                <span class="inline-flex items-center gap-0.5 text-[10px] uppercase font-black px-1.5 py-0.5 rounded bg-emerald-500 text-white whitespace-nowrap">
                                                    <svg class="icon-solid w-2.5 h-2.5"><use href="#ic-star"/></svg>
                                                    Rute Terbaik
                                                </span>
                                            </template>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap"
                                                  :class="{
                                                      'bg-red-100 text-red-700': item.detail_faskes.jenis_faskes === 'rumah_sakit',
                                                      'bg-blue-100 text-blue-700': item.detail_faskes.jenis_faskes === 'puskesmas',
                                                      'bg-emerald-100 text-emerald-700': item.detail_faskes.jenis_faskes === 'klinik_pratama',
                                                      'bg-teal-100 text-teal-700': item.detail_faskes.jenis_faskes === 'klinik_utama',
                                                      'bg-purple-100 text-purple-700': item.detail_faskes.jenis_faskes === 'laboratorium',
                                                      'bg-amber-100 text-amber-800': item.detail_faskes.jenis_faskes === 'upkdk',
                                                      'bg-cyan-100 text-cyan-700': item.detail_faskes.jenis_faskes === 'griya_sehat',
                                                      'bg-sky-100 text-sky-700': item.detail_faskes.jenis_faskes === 'tpmd',
                                                      'bg-indigo-100 text-indigo-700': item.detail_faskes.jenis_faskes === 'tpmdg',
                                                      'bg-pink-100 text-pink-700': item.detail_faskes.jenis_faskes === 'tpmb',
                                                      'bg-lime-100 text-lime-700': item.detail_faskes.jenis_faskes === 'tpmp'
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
                                        <svg class="icon w-4 h-4 flex-shrink-0"><use href="#ic-warning"/></svg>
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
                selectedJenis: ['rumah_sakit', 'puskesmas', 'klinik_pratama', 'klinik_utama', 'laboratorium', 'upkdk', 'griya_sehat', 'tpmd', 'tpmdg', 'tpmb', 'tpmp'],
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

                // Notifikasi "titik terkunci" saat user klik peta padahal titik event sudah ada
                mapLockedNotice: false,
                _lockNoticeTimeout: null,

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

                    // Listener Klik Peta untuk menentukan Titik Event.
                    // FIX BUG: sebelumnya blok ini terdaftar DUA KALI (duplicate listener) sehingga
                    // setiap klik memicu kedua handler sekaligus (setEventPoint bisa terpanggil 2x,
                    // dan notifikasi kunci bisa tidak konsisten muncul). Digabung jadi satu di sini.
                    // Perilaku "kunci titik sampai dihapus manual" tetap dipertahankan sesuai maksud awal.
                    // PENTING: klik di peta HANYA dipakai untuk set titik event — tidak pernah
                    // mengubah sidebarOpen. Penutupan panel tetap murni lewat tombol panel/X.
                    this.map.on('click', (e) => {
                        if (this.eventLat !== null && this.eventLng !== null) {
                            this.mapLockedNotice = true;
                            clearTimeout(this._lockNoticeTimeout);
                            this._lockNoticeTimeout = setTimeout(() => {
                                this.mapLockedNotice = false;
                            }, 1800);
                            return;
                        }
                        this.setEventPoint(e.latlng.lat, e.latlng.lng);
                    });

                    // Muat Semua Data Faskes ke Peta
                    this.loadInitialFaskes();

                    // Listener Kustom untuk 'Jadikan Titik Event' dari Popup Faskes
                    window.addEventListener('set-event-here', (e) => {
                        if (this.eventLat !== null && this.eventLng !== null) return; // konsisten dengan aturan kunci di atas
                        this.setEventPoint(e.detail.lat, e.detail.lng);
                        this.activeTab = 'event';
                    });

                    // Bersihkan floating overlay/badge liar yang mungkin tertinggal di atas
                    // kanvas peta dari inisialisasi sebelumnya. Hanya elemen di luar 4 pojok leaflet yang dihapus.
                    this.cleanupStrayMapOverlays();

                    // FIX: Peta "full screen" saat panel ditutup.
                    // Saat sidebarOpen berubah (HANYA dipicu tombol panel/X di atas — bukan hover/klik peta),
                    // lebar div#map ikut berubah karena aside lg:relative keluar-masuk flex layout.
                    // Leaflet menyimpan ukuran kanvas secara internal dan TIDAK otomatis tahu kalau
                    // kontainernya baru saja berubah ukuran akibat transisi CSS, sehingga peta bisa
                    // terlihat "gepeng"/tidak penuh atau menyisakan area abu-abu sampai ada resize manual.
                    // this.$watch di sini menunggu transisi (duration-300 di elemen <aside>) selesai,
                    // lalu memaksa Leaflet menghitung ulang ukuran kontainernya via invalidateSize().
                    this.$watch('sidebarOpen', () => {
                        setTimeout(() => {
                            if (this.map) {
                                this.map.invalidateSize({ animate: true });
                            }
                        }, 320); // sedikit lebih lama dari durasi transisi (300ms) di kelas Tailwind aside
                    });
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
                    if (this.selectedJenis.length < 11) count++;
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
                    this.selectedJenis = ['rumah_sakit', 'puskesmas', 'klinik_pratama', 'klinik_utama', 'laboratorium', 'upkdk', 'griya_sehat', 'tpmd', 'tpmdg', 'tpmb', 'tpmp'];
                    this.applyDynamicFilters();
                },

                clearAllJenis() {
                    this.selectedJenis = [];
                    this.applyDynamicFilters();
                },

                resetDynamicFilters() {
                    this.selectedJenis = ['rumah_sakit', 'puskesmas', 'klinik_pratama', 'klinik_utama', 'laboratorium', 'upkdk', 'griya_sehat', 'tpmd', 'tpmdg', 'tpmb', 'tpmp'];
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

                // Menetapkan Titik Event Baru. Marker di-reuse (bukan clearLayers()+recreate) supaya
                // transisi antar titik tetap mulus dan tidak memicu race condition animasi Leaflet.
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
                        // Reuse marker yang sudah ada — jangan clearLayers()+recreate.
                        this.eventMarker.setLatLng([lat, lng]);
                        this.eventMarker.setPopupContent(popupHtml);
                    } else {
                        // Ikon marker event pakai SVG (bukan emoji), tetap dari sprite yang sama.
                        const eventIcon = L.divIcon({
                            className: 'event-pulse',
                            html: `
                                <div style="background-color: #ef4444; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(239,68,68,0.5); border: 2px solid #ffffff;">
                                    <svg style="transform: rotate(45deg); width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            `,
                            iconSize: [32, 32],
                            iconAnchor: [16, 32],
                            popupAnchor: [0, -32]
                        });

                        // Titik event tidak lagi draggable — perpindahan titik dilakukan secara eksplisit:
                        // hapus dulu (resetEvent) lalu klik titik baru, supaya tidak ada risiko tergeser
                        // tanpa sengaja saat peta sedang dipakai untuk kondisi darurat.
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
                    if (this.selectedJenis.length > 0 && this.selectedJenis.length < 11) {
                        this.selectedJenis.forEach(j => params.append('jenis_faskes[]', j));
                    }
                    if (this.filterAmbulans) params.append('has_ambulans', '1');
                    if (this.filterBpjs) params.append('has_bpjs', '1');
                    if (this.filterRawatInap) params.append('has_rawat_inap', '1');
                    if (this.filterPoned) params.append('has_poned', '1');

                    // Pakai helper url() Laravel (bukan path hardcode) supaya tetap benar
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
                    if (this.selectedJenis.length > 0 && this.selectedJenis.length < 11) {
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
                        'klinik_pratama': { color: '#10b981', label: 'KP' },
                        'klinik_utama':   { color: '#14b8a6', label: 'KU' },
                        'laboratorium':   { color: '#8b5cf6', label: 'LAB' },
                        'upkdk':          { color: '#f59e0b', label: 'UPK' },
                        'griya_sehat':    { color: '#06b6d4', label: 'GS' },
                        'tpmd':           { color: '#0ea5e9', label: 'TD' },
                        'tpmdg':          { color: '#6366f1', label: 'TG' },
                        'tpmb':           { color: '#ec4899', label: 'TB' },
                        'tpmp':           { color: '#84cc16', label: 'TP' }
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

                // Popup HTML Faskes (string HTML mentah yang dirender Leaflet, jadi boleh pakai <svg><use></use></svg>
                // yang menunjuk ke sprite ikon di awal <body> — tidak lewat x-text sehingga aman disisipi markup)
                createFaskesPopupHtml(faskes) {
                    const jenisText = this.formatJenis(faskes.jenis_faskes);
                    const detail = faskes.detail || {};
                    const icon = (name, cls = 'w-3 h-3') =>
                        `<svg class="icon ${cls} inline-block align-[-2px]"><use href="#${name}"/></svg>`;

                    let badgeHtml = '';
                    const totalAmbulans = (parseInt(detail.ambulans_transport) || 0) +
                                          (parseInt(detail.ambulans_roda_dua) || 0) +
                                          (parseInt(detail.ambulans_gadar) || 0) +
                                          (parseInt(detail.ambulans) || 0);

                    if (detail.tipe_rs) {
                        badgeHtml += `<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-800 mr-1">Tipe ${detail.tipe_rs}</span>`;
                    }
                    if (detail.kategori_layanan) {
                        const katLabel = detail.kategori_layanan === 'rawat_inap' ? 'Rawat Inap' : 'Rawat Jalan';
                        badgeHtml += `<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 mr-1">${katLabel}</span>`;
                    }
                    if (totalAmbulans > 0) {
                        badgeHtml += `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 mr-1">${icon('ic-medical')} ${totalAmbulans} Ambulans</span>`;
                    }
                    if (detail.poned === 'Ya PONED') {
                        badgeHtml += `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800 mr-1">${icon('ic-heart')} PONED</span>`;
                    }
                    if (detail.ponek === 'Ya PONEK') {
                        badgeHtml += `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 text-purple-800 mr-1">${icon('ic-shield')} PONEK</span>`;
                    }
                    if (detail.mampu_salin === 'Ya') {
                        badgeHtml += '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-pink-100 text-pink-800 mr-1">Mampu Salin</span>';
                    }
                    if (detail.bpjs === true || detail.bpjs === 1 || detail.bpjs === 'Ya') {
                        badgeHtml += `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 mr-1">${icon('ic-card')} BPJS</span>`;
                    }
                    if (detail.jumlah_tempat_tidur > 0 || detail.bed_rawat_inap > 0) {
                        const bed = detail.jumlah_tempat_tidur || detail.bed_rawat_inap;
                        badgeHtml += `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-800 mr-1">${icon('ic-bed')} ${bed} Bed</span>`;
                    }
                    if (detail.is_pustu === 'Ya') {
                        badgeHtml += '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 mr-1">Pustu</span>';
                    }
                    if (detail.is_pkd === 'Ya') {
                        badgeHtml += '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-orange-100 text-orange-800 mr-1">PKD</span>';
                    }
                    if (detail.kepemilikan) {
                        badgeHtml += `<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-medium bg-slate-100 text-slate-600 mr-1">${detail.kepemilikan}</span>`;
                    }
                    if (faskes.jenis_faskes === 'griya_sehat' && detail.pj) {
                        badgeHtml += `<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-medium bg-cyan-100 text-cyan-800 mr-1">PJ: ${detail.pj}</span>`;
                    }

                    return `
                        <div class="text-xs p-1 max-w-[240px]">
                            <div class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 mb-1">
                                ${jenisText}
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm leading-tight">${faskes.nama}</h4>
                            <p class="text-slate-500 text-[11px] mt-1">${faskes.alamat || '-'}</p>
                            ${faskes.nomor_telepon ? `<p class="text-slate-600 text-[11px] mt-1 flex items-center gap-1">${icon('ic-phone', 'w-3.5 h-3.5')} ${faskes.nomor_telepon}</p>` : ''}
                            ${badgeHtml ? `<div class="mt-2 flex flex-wrap gap-1">${badgeHtml}</div>` : ''}
                            <hr class="my-2 border-slate-100">
                            <button onclick="window.dispatchEvent(new CustomEvent('set-event-here', {detail: {lat: ${faskes.latitude}, lng: ${faskes.longitude}}}))"
                                    class="w-full text-center py-1 rounded bg-blue-50 text-blue-600 hover:bg-blue-100 font-semibold text-[11px] transition flex items-center justify-center gap-1">
                                ${icon('ic-pin', 'w-3.5 h-3.5')} Jadikan Titik Event
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
                        'upkdk': 'UPKDK',
                        'griya_sehat': 'Griya Sehat',
                        'tpmd': 'TPMD (Dokter)',
                        'tpmdg': 'TPMDG (Dokter Gigi)',
                        'tpmb': 'TPMB (Bidan)',
                        'tpmp': 'TPMP (Perawat)'
                    };
                    return map[jenis] || jenis;
                }
            }
        }
    </script>
</body>
</html>