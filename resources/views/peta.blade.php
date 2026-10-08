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

    <!-- Leaflet MarkerCluster CSS (v1.5.3) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet MarkerCluster JS (v1.5.3) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js"></script>
    <script src="{{ url('/js/faskes-basemaps.js') }}"></script>

    <style>
        html, body {
            height: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        /* Perbaikan CSS Leaflet - Reset Tailwind pada img & svg Leaflet (cegah bug marker melayang/rusak) */
        .leaflet-container img,
        .leaflet-container svg {
            max-width: none !important;
            max-height: none !important;
        }

        /* Perbaikan CSS Leaflet - Hilangkan kotak hitam & border default */
        .leaflet-container {
            font-family: inherit !important;
        }
        path.leaflet-interactive:focus,
        .leaflet-container path:focus {
            outline: none !important;
        }

        /* Rapikan styling MarkerCluster agar serasi dengan Tailwind */
        .marker-cluster-small {
            background-color: rgba(59, 130, 246, 0.35) !important;
        }
        .marker-cluster-small div {
            background-color: rgba(37, 99, 235, 0.9) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }
        .marker-cluster-medium {
            background-color: rgba(245, 158, 11, 0.35) !important;
        }
        .marker-cluster-medium div {
            background-color: rgba(217, 119, 6, 0.9) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }
        .marker-cluster-large {
            background-color: rgba(239, 68, 68, 0.35) !important;
        }
        .marker-cluster-large div {
            background-color: rgba(220, 38, 38, 0.9) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
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

        <!-- Legend Pills (Tablet & Desktop) dengan Indikator Status Zoom LOD (Tugas 9) -->
        <div class="hidden md:flex flex-wrap items-center gap-1.5 text-[11px] xl:text-xs">
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-red-50 text-red-700 font-medium border border-red-200 transition-all duration-200"
                  :class="isTypeUnlocked('rumah_sakit') ? 'opacity-100 shadow-xs' : 'opacity-40 grayscale-[40%] border-dashed'"
                  :title="getTypeZoomStatusTooltip('rumah_sakit')">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 mr-1.5"></span>
                <span>Rumah Sakit</span>
                <template x-if="!isTypeUnlocked('rumah_sakit')">
                    <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-medium border border-blue-200 transition-all duration-200"
                  :class="isTypeUnlocked('puskesmas') ? 'opacity-100 shadow-xs' : 'opacity-40 grayscale-[40%] border-dashed'"
                  :title="getTypeZoomStatusTooltip('puskesmas')">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mr-1.5"></span>
                <span>Puskesmas</span>
                <template x-if="!isTypeUnlocked('puskesmas')">
                    <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium border border-emerald-200 transition-all duration-200"
                  :class="isTypeUnlocked('klinik_pratama') || isTypeUnlocked('klinik_utama') ? 'opacity-100 shadow-xs' : 'opacity-40 grayscale-[40%] border-dashed'"
                  :title="getTypeZoomStatusTooltip('klinik_pratama')">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5"></span>
                <span>Klinik</span>
                <template x-if="!isTypeUnlocked('klinik_pratama') && !isTypeUnlocked('klinik_utama')">
                    <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 font-medium border border-purple-200 transition-all duration-200"
                  :class="isTypeUnlocked('laboratorium') ? 'opacity-100 shadow-xs' : 'opacity-40 grayscale-[40%] border-dashed'"
                  :title="getTypeZoomStatusTooltip('laboratorium')">
                <span class="w-2.5 h-2.5 rounded-full bg-purple-500 mr-1.5"></span>
                <span>Lab</span>
                <template x-if="!isTypeUnlocked('laboratorium')">
                    <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 font-medium border border-amber-200 transition-all duration-200"
                  :class="isTypeUnlocked('upkdk') ? 'opacity-100 shadow-xs' : 'opacity-40 grayscale-[40%] border-dashed'"
                  :title="getTypeZoomStatusTooltip('upkdk')">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-1.5"></span>
                <span>UPKDK</span>
                <template x-if="!isTypeUnlocked('upkdk')">
                    <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 font-medium border border-cyan-200 transition-all duration-200"
                  :class="isTypeUnlocked('griya_sehat') ? 'opacity-100 shadow-xs' : 'opacity-40 grayscale-[40%] border-dashed'"
                  :title="getTypeZoomStatusTooltip('griya_sehat')">
                <span class="w-2.5 h-2.5 rounded-full bg-cyan-500 mr-1.5"></span>
                <span>Griya Sehat</span>
                <template x-if="!isTypeUnlocked('griya_sehat')">
                    <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
            </span>
            <span class="inline-flex items-center px-2 xl:px-2.5 py-1 rounded-full bg-sky-50 text-sky-700 font-medium border border-sky-200 transition-all duration-200"
                  :class="isTypeUnlocked('tpmd') || isTypeUnlocked('tpmdg') || isTypeUnlocked('tpmb') || isTypeUnlocked('tpmp') ? 'opacity-100 shadow-xs' : 'opacity-40 grayscale-[40%] border-dashed'"
                  :title="getTypeZoomStatusTooltip('tpmd')">
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500 mr-1.5"></span>
                <span>TPM</span>
                <template x-if="!isTypeUnlocked('tpmd') && !isTypeUnlocked('tpmdg') && !isTypeUnlocked('tpmb') && !isTypeUnlocked('tpmp')">
                    <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
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
        <div id="map" class="flex-1 h-full w-full z-0 cursor-crosshair relative">
            <!-- Floating Notice: Zoom in untuk menampilkan jenis berikutnya (Tugas 9) -->
            <div x-cloak x-show="getPendingTypesText() && !showAllTypesAtAllZooms"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="absolute top-3 left-1/2 -translate-x-1/2 z-[400] pointer-events-auto max-w-[90%] sm:max-w-md">
                <div class="bg-white/95 backdrop-blur-md px-3.5 py-1.5 rounded-full shadow-lg border border-slate-200/80 flex items-center gap-2 text-xs text-slate-700">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse flex-shrink-0"></span>
                    <span class="truncate">
                        Perbesar peta (<span class="font-semibold text-blue-600">Zoom In</span>) untuk melihat:
                        <b class="text-slate-900 font-semibold" x-text="getPendingTypesText()"></b>
                    </span>
                    <button type="button" @click="zoomInStep()" title="Zoom In"
                            class="ml-1 px-2 py-0.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-[11px] transition">
                        +
                    </button>
                </div>
            </div>

            <!-- Toast Titik Event Terkunci -->
            <div x-cloak x-show="mapLockedNotice"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute top-14 left-1/2 -translate-x-1/2 z-[400] pointer-events-none">
                <div class="bg-slate-900/90 backdrop-blur-sm text-white px-3 py-1.5 rounded-full shadow-lg text-xs flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Titik event sudah terkunci. Klik &lsquo;Hapus Titik&rsquo; di panel untuk memindahkannya.</span>
                </div>
            </div>
        </div>

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

                <!-- Header Status Filter & Count Marker -->
                <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3 flex flex-col gap-2 text-xs">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-blue-900">Marker Faskes di Layar</span>
                            <p class="text-blue-700 text-[11px]">
                                Menampilkan <b x-text="mountedCount"></b> dari <span x-text="totalFilteredCount"></span> faskes
                                <span class="text-slate-400 font-normal" x-text="`(Zoom: ${currentZoom})`"></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <template x-if="isFiltering || isFetchingType">
                                <div class="flex items-center gap-1 text-blue-600 font-semibold text-[11px]">
                                    <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span x-text="loadingStatusText || 'Menyaring...'"></span>
                                </div>
                            </template>
                            <button type="button" @click="resetDynamicFilters()"
                                    class="text-[11px] px-2 py-1 rounded bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 font-medium transition focus:outline-none focus:ring-2 focus:ring-blue-300">
                                Reset Filter
                            </button>
                        </div>
                    </div>

                    <!-- Toggle: Tampilkan semua jenis di semua zoom (Tugas 9) -->
                    <div class="pt-2 border-t border-blue-200/60 flex items-center justify-between">
                        <label class="text-[11px] text-slate-700 font-medium cursor-pointer flex items-center gap-1.5 select-none" for="toggle-all-zooms">
                            <span>Tampilkan semua jenis di semua zoom</span>
                            <span class="text-[10px] text-slate-400" x-show="!showAllTypesAtAllZooms">(Hemat DOM: LOD aktif)</span>
                        </label>
                        <input type="checkbox" id="toggle-all-zooms" x-model="showAllTypesAtAllZooms" @change="onToggleAllZooms()"
                               class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4 cursor-pointer">
                    </div>
                </div>

                <!-- Progress Rendering Marker Bertahap (Tugas 8B) -->
                <template x-if="isRenderingClusters && clusterProgressTotal > 0">
                    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-2.5 space-y-1.5 transition">
                        <div class="flex items-center justify-between text-[11px] text-indigo-800 font-medium">
                            <span class="flex items-center gap-1.5">
                                <svg class="animate-spin h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Memuat marker bertahap...
                            </span>
                            <span class="font-bold" x-text="`${clusterProgressProcessed} / ${clusterProgressTotal}`"></span>
                        </div>
                        <div class="w-full bg-indigo-200 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-100"
                                 :style="`width: ${Math.round((clusterProgressProcessed / clusterProgressTotal) * 100)}%`"></div>
                        </div>
                    </div>
                </template>

                <!-- Pagar Sanitasi: Notifikasi Koordinat Tidak Valid (Tugas 8B) -->
                <template x-if="invalidCoordinateCount > 0">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-2.5 flex items-start gap-2 text-[11px] text-amber-800">
                        <svg class="icon w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"><use href="#ic-warning"/></svg>
                        <span><b x-text="invalidCoordinateCount"></b> titik dilewati karena koordinat tidak valid / di luar batas Kab. Banyumas.</span>
                    </div>
                </template>

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

                <!-- FILTER ATRIBUT LANJUTAN (AKORDEON TUGAS 4) -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between px-1">
                        <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <svg class="icon w-4 h-4 text-blue-600"><use href="#ic-clipboard"/></svg>
                            <span>Filter Atribut Lanjutan</span>
                        </label>
                        <span class="text-[10px] text-slate-400 font-medium">Opsional &amp; Komprehensif</span>
                    </div>

                    <!-- 1. Akordeon Ambulans & Kapasitas -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden"
                         x-show="hasAnyJenis(['rumah_sakit', 'puskesmas', 'klinik_pratama', 'klinik_utama', 'upkdk', 'griya_sehat'])">
                        <button type="button"
                                @click="accordionOpen.ambulans = !accordionOpen.ambulans"
                                class="w-full p-3 text-left font-bold text-xs text-slate-800 flex items-center justify-between hover:bg-slate-50 transition">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="icon w-3.5 h-3.5"><use href="#ic-medical"/></svg>
                                </span>
                                <span>Ambulans &amp; Kapasitas</span>
                                <span x-show="countFilterAmbulansKapasitas() > 0"
                                      class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-600 text-white"
                                      x-text="countFilterAmbulansKapasitas()"></span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transform transition-transform"
                                 :class="accordionOpen.ambulans ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="accordionOpen.ambulans" class="p-3 pt-0 border-t border-slate-100 space-y-3 text-xs bg-slate-50/50">
                            <!-- Total Ambulans Minimal -->
                            <div class="mt-2.5">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[11px] font-semibold text-slate-700">Total Ambulans Minimal</label>
                                    <span class="text-[11px] font-bold text-blue-600" x-text="minTotalAmbulans > 0 ? `${minTotalAmbulans} Unit` : 'Semua (0)'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="if(minTotalAmbulans > 0) { minTotalAmbulans--; onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">-</button>
                                    <input type="range" min="0" max="10" step="1" x-model.number="minTotalAmbulans" @input="onNumberFilterChange()"
                                           class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                                    <button type="button" @click="if(minTotalAmbulans < 10) { minTotalAmbulans++; onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">+</button>
                                </div>
                                <span class="text-[10px] text-slate-400 italic">Puskesmas, RS, Klinik Pratama &amp; Klinik Utama</span>
                            </div>

                            <!-- Ambulans Gadar Minimal (Khusus RS) -->
                            <div x-show="isJenisSelected('rumah_sakit')" class="pt-2 border-t border-slate-200/60">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[11px] font-semibold text-slate-700">Ambulans Gadar Minimal (RS)</label>
                                    <span class="text-[11px] font-bold text-amber-600" x-text="minAmbulansGadar > 0 ? `${minAmbulansGadar} Unit` : 'Semua (0)'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="if(minAmbulansGadar > 0) { minAmbulansGadar--; onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">-</button>
                                    <input type="range" min="0" max="10" step="1" x-model.number="minAmbulansGadar" @input="onNumberFilterChange()"
                                           class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-amber-600">
                                    <button type="button" @click="if(minAmbulansGadar < 10) { minAmbulansGadar++; onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">+</button>
                                </div>
                            </div>

                            <!-- Ambulans Roda Dua Minimal (Khusus Puskesmas) -->
                            <div x-show="isJenisSelected('puskesmas')" class="pt-2 border-t border-slate-200/60">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[11px] font-semibold text-slate-700">Ambulans Roda Dua Minimal (Puskesmas)</label>
                                    <span class="text-[11px] font-bold text-blue-600" x-text="minAmbulansRodaDua > 0 ? `${minAmbulansRodaDua} Unit` : 'Semua (0)'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="if(minAmbulansRodaDua > 0) { minAmbulansRodaDua--; onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">-</button>
                                    <input type="range" min="0" max="10" step="1" x-model.number="minAmbulansRodaDua" @input="onNumberFilterChange()"
                                           class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                                    <button type="button" @click="if(minAmbulansRodaDua < 10) { minAmbulansRodaDua++; onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">+</button>
                                </div>
                            </div>

                            <!-- Jumlah TT Rawat Inap Minimal -->
                            <div x-show="hasAnyJenis(['puskesmas', 'klinik_pratama', 'klinik_utama'])" class="pt-2 border-t border-slate-200/60">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[11px] font-semibold text-slate-700">Jumlah TT (Tempat Tidur) Minimal</label>
                                    <span class="text-[11px] font-bold text-indigo-600" x-text="minBed > 0 ? `${minBed} Bed` : 'Semua (0)'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="if(minBed > 0) { minBed = Math.max(0, minBed - 5); onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">-</button>
                                    <input type="range" min="0" max="100" step="5" x-model.number="minBed" @input="onNumberFilterChange()"
                                           class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                                    <button type="button" @click="if(minBed < 100) { minBed += 5; onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">+</button>
                                </div>
                                <p class="text-[10px] text-slate-400 italic mt-0.5">*RS belum memiliki data jumlah TT di sistem</p>
                            </div>

                            <!-- Jumlah SDM Minimal -->
                            <div x-show="hasAnyJenis(['puskesmas', 'klinik_pratama', 'upkdk', 'griya_sehat'])" class="pt-2 border-t border-slate-200/60">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[11px] font-semibold text-slate-700">Jumlah Tenaga SDM Minimal</label>
                                    <span class="text-[11px] font-bold text-emerald-600" x-text="minSdm > 0 ? `${minSdm} Orang` : 'Semua (0)'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="if(minSdm > 0) { minSdm = Math.max(0, minSdm - 5); onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">-</button>
                                    <input type="range" min="0" max="150" step="5" x-model.number="minSdm" @input="onNumberFilterChange()"
                                           class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-emerald-600">
                                    <button type="button" @click="if(minSdm < 150) { minSdm += 5; onNumberFilterChange(); }"
                                            class="w-7 h-7 rounded border border-slate-200 bg-white hover:bg-slate-100 font-bold text-slate-600 flex items-center justify-center">+</button>
                                </div>
                                <span class="text-[10px] text-slate-400 italic">Puskesmas, Klinik Pratama, UPKDK, Griya Sehat</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Akordeon Kategori Layanan -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <button type="button"
                                @click="accordionOpen.kategori = !accordionOpen.kategori"
                                class="w-full p-3 text-left font-bold text-xs text-slate-800 flex items-center justify-between hover:bg-slate-50 transition">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="icon w-3.5 h-3.5"><use href="#ic-hospital"/></svg>
                                </span>
                                <span>Kategori Layanan</span>
                                <span x-show="countFilterKategori() > 0"
                                      class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-600 text-white"
                                      x-text="countFilterKategori()"></span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transform transition-transform"
                                 :class="accordionOpen.kategori ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="accordionOpen.kategori" class="p-3 pt-0 border-t border-slate-100 space-y-2.5 text-xs bg-slate-50/50">
                            <!-- Kepemilikan (Klinik Pratama, Klinik Utama, Lab) -->
                            <div x-show="hasAnyJenis(['klinik_pratama', 'klinik_utama', 'laboratorium'])" class="mt-2.5">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Kepemilikan (Klinik &amp; Lab)</label>
                                <select x-model="kepemilikan" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Kepemilikan</option>
                                    <option value="Swasta">Swasta</option>
                                    <option value="Pemerintah">Pemerintah</option>
                                </select>
                            </div>

                            <!-- Tipe RS (Rumah Sakit) -->
                            <div x-show="isJenisSelected('rumah_sakit')">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tipe Rumah Sakit</label>
                                <select x-model="tipeRs" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Tipe RS</option>
                                    <option value="A">Kelas A</option>
                                    <option value="B">Kelas B</option>
                                    <option value="C">Kelas C</option>
                                    <option value="D">Kelas D</option>
                                    <option value="D Pratama">Kelas D Pratama</option>
                                </select>
                            </div>

                            <!-- Kategori Puskesmas -->
                            <div x-show="isJenisSelected('puskesmas')">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Kategori Puskesmas</label>
                                <select x-model="kategoriPuskesmas" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Kategori</option>
                                    <option value="rawat_inap">Rawat Inap</option>
                                    <option value="rawat_jalan">Non Rawat Inap (Rawat Jalan)</option>
                                </select>
                            </div>

                            <!-- Wilayah Puskesmas -->
                            <div x-show="isJenisSelected('puskesmas')">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Wilayah Puskesmas</label>
                                <select x-model="wilayahPuskesmas" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Wilayah</option>
                                    <option value="perkotaan">Perkotaan</option>
                                    <option value="pedesaan">Perdesaan</option>
                                </select>
                            </div>

                            <!-- Kemampuan Persalinan Puskesmas -->
                            <div x-show="isJenisSelected('puskesmas')">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Persalinan Puskesmas</label>
                                <select x-model="persalinanPuskesmas" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Kemampuan</option>
                                    <option value="poned">PONED</option>
                                    <option value="mampu_salin">NON PONED (Mampu Salin)</option>
                                    <option value="tidak_mampu_salin">NON PONED (Tidak Mampu Salin)</option>
                                </select>
                            </div>

                            <!-- PONEK Rumah Sakit -->
                            <div x-show="isJenisSelected('rumah_sakit')">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">PONEK Rumah Sakit</label>
                                <select x-model="ponekRs" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua PONEK</option>
                                    <option value="ponek">PONEK</option>
                                    <option value="non_ponek">NON PONEK</option>
                                </select>
                            </div>

                            <!-- Kategori Layanan Klinik (Pratama & Utama) -->
                            <div x-show="hasAnyJenis(['klinik_pratama', 'klinik_utama'])">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Layanan Klinik (Pratama &amp; Utama)</label>
                                <select x-model="kategoriLayananKlinik" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Layanan</option>
                                    <option value="rawat_jalan">Rawat Jalan</option>
                                    <option value="rawat_inap">Rawat Inap</option>
                                </select>
                            </div>

                            <!-- BPJS Klinik -->
                            <div x-show="hasAnyJenis(['klinik_pratama', 'klinik_utama'])">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Kerja Sama BPJS (Khusus Klinik)</label>
                                <select x-model="bpjsKlinik" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua (Mitra &amp; Non Mitra)</option>
                                    <option value="ya">Bekerja sama BPJS</option>
                                    <option value="tidak">Tidak Bekerja sama</option>
                                </select>
                            </div>

                            <!-- Status Operasional Faskes -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Status Operasional Faskes</label>
                                <select x-model="statusOperasional" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="aktif">Aktif Saja (Default)</option>
                                    <option value="nonaktif">Nonaktif Saja</option>
                                    <option value="semua">Semua Status (Aktif &amp; Nonaktif)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Akordeon Izin Operasional -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <button type="button"
                                @click="accordionOpen.izin = !accordionOpen.izin"
                                class="w-full p-3 text-left font-bold text-xs text-slate-800 flex items-center justify-between hover:bg-slate-50 transition">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="icon w-3.5 h-3.5"><use href="#ic-doc"/></svg>
                                </span>
                                <span>Izin Operasional</span>
                                <span x-show="statusIzin !== ''"
                                      class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-600 text-white">1</span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transform transition-transform"
                                 :class="accordionOpen.izin ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="accordionOpen.izin" class="p-3 pt-0 border-t border-slate-100 space-y-2 text-xs bg-slate-50/50">
                            <div class="mt-2.5">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Masa Berlaku Izin Operasional</label>
                                <select x-model="statusIzin" @change="applyDynamicFilters()"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Status Izin</option>
                                    <option value="berlaku">Berlaku (&gt; 6 bulan)</option>
                                    <option value="berakhir_6_bulan">Segera Berakhir (&le; 6 bulan)</option>
                                    <option value="berakhir_3_bulan">Segera Berakhir (&le; 3 bulan)</option>
                                    <option value="berakhir_1_bulan">Segera Berakhir (&le; 1 bulan)</option>
                                    <option value="kedaluwarsa">Kedaluwarsa</option>
                                    <option value="belum_diisi">Belum Diisi / Tanpa Data</option>
                                </select>
                                <span class="text-[10px] text-slate-400 mt-1 block">Memeriksa kolom masa izin seluruh faskes terkait</span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Akordeon Kolom Tambahan (Custom Fields) -->
                    @if(isset($activeFieldDefinitions) && $activeFieldDefinitions->count() > 0)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <button type="button"
                                @click="accordionOpen.custom = !accordionOpen.custom"
                                class="w-full p-3 text-left font-bold text-xs text-slate-800 flex items-center justify-between hover:bg-slate-50 transition">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="icon w-3.5 h-3.5"><use href="#ic-table"/></svg>
                                </span>
                                <span>Kolom Tambahan (Kustom)</span>
                                <span x-show="countFilterCustom() > 0"
                                      class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-600 text-white"
                                      x-text="countFilterCustom()"></span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transform transition-transform"
                                 :class="accordionOpen.custom ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="accordionOpen.custom" class="p-3 pt-0 border-t border-slate-100 space-y-2.5 text-xs bg-slate-50/50">
                            @foreach($activeFieldDefinitions as $def)
                                <div class="mt-2.5"
                                     @if($def->jenis_faskes) x-show="isJenisSelected('{{ $def->jenis_faskes }}')" @endif>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                        {{ $def->label }}
                                        @if($def->jenis_faskes)
                                            <span class="text-[10px] text-slate-400 font-normal">({{ $def->jenis_faskes }})</span>
                                        @endif
                                    </label>
                                    <select x-model="customFilter['{{ $def->field_key }}']" @change="applyDynamicFilters()"
                                            class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Semua {{ $def->label }}</option>
                                        @foreach($def->options as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
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
        // Konfigurasi Level of Detail (LOD) Berbasis Zoom (Tugas 9)
        const BASE_ZOOM = 12; // Zoom default Kabupaten Banyumas di peta utama
        const SHOW_SINGLE_TYPE_ALWAYS = true; // Jika user memilih TEPAT SATU jenis, tampilkan tanpa batas zoom
        const ZOOM_THRESHOLDS = {
            'rumah_sakit':    BASE_ZOOM,     // 12 - Hanya Rumah Sakit di zoom awal
            'puskesmas':      BASE_ZOOM + 1, // 13
            'klinik_utama':   BASE_ZOOM + 2, // 14
            'klinik_pratama': BASE_ZOOM + 3, // 15
            'laboratorium':   BASE_ZOOM + 3, // 15
            'griya_sehat':    BASE_ZOOM + 4, // 16
            'upkdk':          BASE_ZOOM + 4, // 16
            'tpmd':           BASE_ZOOM + 5, // 17
            'tpmdg':          BASE_ZOOM + 5, // 17
            'tpmb':           BASE_ZOOM + 5, // 17
            'tpmp':           BASE_ZOOM + 5  // 17
        };

        const ALL_FASKES_TYPES = [
            'rumah_sakit', 'puskesmas', 'klinik_pratama', 'klinik_utama',
            'laboratorium', 'upkdk', 'griya_sehat', 'tpmd', 'tpmdg', 'tpmb', 'tpmp'
        ];

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

                // State Akordeon Filter Lanjutan (Tugas 4)
                accordionOpen: {
                    ambulans: false,
                    kategori: false,
                    izin: false,
                    custom: false
                },
                filterDebounceTimer: null,
                filterAbortController: null,

                // Nilai Filter Lanjutan (Tugas 4)
                minTotalAmbulans: 0,
                minAmbulansGadar: 0,
                minAmbulansRodaDua: 0,
                minBed: 0,
                minSdm: 0,
                kepemilikan: '',
                tipeRs: '',
                kategoriPuskesmas: '',
                wilayahPuskesmas: '',
                persalinanPuskesmas: '',
                ponekRs: '',
                kategoriLayananKlinik: '',
                bpjsKlinik: '',
                statusIzin: '',
                statusOperasional: 'aktif',
                customFilter: {},

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
                faskesLayerGroup: null, // L.layerGroup standar (Tugas 9: menggantikan L.markerClusterGroup)
                eventLayerGroup: null,
                routeLayerGroup: null,
                radiusCircle: null,
                eventMarker: null,
                markerMap: {}, // id -> L.marker
                iconCache: {},
                faskesDetailCache: new Map(), // id -> full detail data

                // State Level of Detail (LOD) & Culling (Tugas 9)
                currentZoom: BASE_ZOOM,
                showAllTypesAtAllZooms: false,
                typeCounts: {}, // jenis -> count
                totalFilteredCount: 0,
                mountedCount: 0,
                typeDataCache: new Map(), // key `${filterSignature}__${jenis}` -> Array<item>
                activeFetchControllers: new Map(), // jenis -> AbortController
                isFetchingType: false,
                loadingStatusText: '',
                cullDebounceTimer: null,
                mountBatchId: 0, // Batalkan batch mounting rAF sebelumnya jika view berubah

                invalidCoordinateCount: 0,
                currentRequestId: 0,
                basemapHelper: null,
                currentBasemapName: 'Peta Jalan',

                // Data Hasil
                faskesList: [],
                hasilAnalisis: [],

                initMap() {
                    // Default center: Purwokerto, Kabupaten Banyumas (-7.424364, 109.230345)
                    this.map = L.map('map', {
                        zoomControl: false,
                        markerZoomAnimation: true,
                        fadeAnimation: true
                    }).setView([-7.424364, 109.230345], BASE_ZOOM);

                    this.currentZoom = this.map.getZoom();

                    // Posisi Zoom Control di pojok kiri bawah
                    L.control.zoom({ position: 'bottomleft' }).addTo(this.map);

                    // Basemaps (Peta Jalan, Satelit, Satelit + Label) dengan persistensi localStorage
                    this.basemapHelper = FaskesBasemaps.add(this.map, { position: 'topleft' });
                    if (this.basemapHelper && this.basemapHelper.activeName) {
                        this.currentBasemapName = this.basemapHelper.activeName;
                    }

                    this.map.on('baselayerchange', (e) => {
                        if (e && e.name) {
                            this.updateBoundaryContrast(e.name);
                        }
                    });

                    // Layer Groups Leaflet:
                    // 1. Batas Kabupaten (Garis pembatas non-interaktif)
                    // 2. Faskes Markers (LayerGroup standar tanpa cluster - Tugas 9)
                    // 3. Rute OSRM
                    // 4. Titik Event & Radius Circle
                    this.kabupatenLayerGroup = L.layerGroup().addTo(this.map);
                    this.faskesLayerGroup = L.layerGroup().addTo(this.map);
                    this.routeLayerGroup = L.layerGroup().addTo(this.map);
                    this.eventLayerGroup = L.layerGroup().addTo(this.map);

                    // Inisialisasi cache icon satu kali untuk 11 jenis faskes (Tugas 8B)
                    this.initIconCache();

                    // Muat Batas Luar Kabupaten Banyumas (Non-Interaktif)
                    this.loadKabupatenBoundary();

                    // Viewport Culling & LOD Synchronizer pada event moveend / zoomend (Tugas 9)
                    // Debounce 150 ms agar tidak membebani browser saat scrolling/panning cepat
                    this.map.on('moveend', () => {
                        this.currentZoom = this.map.getZoom();
                        clearTimeout(this.cullDebounceTimer);
                        this.cullDebounceTimer = setTimeout(() => {
                            this.syncMarkersForCurrentView(false);
                        }, 150);
                    });

                    // Listener Klik Peta untuk menentukan Titik Event
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

                    // Muat Data Awal Faskes (LOD zoom: hanya jenis yang aktif di BASE_ZOOM)
                    this.loadInitialFaskes();

                    // Listener Kustom untuk 'Jadikan Titik Event' dari Popup Faskes
                    window.addEventListener('set-event-here', (e) => {
                        if (this.eventLat !== null && this.eventLng !== null) return;
                        this.setEventPoint(e.detail.lat, e.detail.lng);
                        this.activeTab = 'event';
                    });

                    // Bersihkan floating overlay/badge liar di kanvas peta
                    this.cleanupStrayMapOverlays();

                    // Invalidate size saat sidebar ditutup/dibuka
                    this.$watch('sidebarOpen', () => {
                        setTimeout(() => {
                            if (this.map) {
                                this.map.invalidateSize({ animate: true });
                            }
                        }, 320);
                    });
                },

                // Helper: Tombol Zoom In satu tingkat dari floating notice
                zoomInStep() {
                    if (this.map) {
                        this.map.zoomIn();
                    }
                },

                // Helper LOD: Apakah suatu jenis faskes sudah boleh tampil di zoom saat ini? (Tugas 9)
                isTypeUnlocked(jenis) {
                    if (this.showAllTypesAtAllZooms) return true;
                    if (SHOW_SINGLE_TYPE_ALWAYS && this.selectedJenis.length === 1 && this.selectedJenis.includes(jenis)) {
                        return true;
                    }
                    const minZoom = ZOOM_THRESHOLDS[jenis] !== undefined ? ZOOM_THRESHOLDS[jenis] : BASE_ZOOM;
                    return (this.currentZoom || BASE_ZOOM) >= minZoom;
                },

                // Tooltip info ambang zoom untuk legend chips header
                getTypeZoomStatusTooltip(jenis) {
                    const minZoom = ZOOM_THRESHOLDS[jenis] !== undefined ? ZOOM_THRESHOLDS[jenis] : BASE_ZOOM;
                    if (this.isTypeUnlocked(jenis)) {
                        return `${this.formatJenis(jenis)}: Aktif (Zoom ${this.currentZoom} >= ${minZoom})`;
                    }
                    return `${this.formatJenis(jenis)}: Terkunci (Perbesar peta hingga Zoom >= ${minZoom})`;
                },

                // Floating Notice Text: Jenis faskes yang dipilih user namun belum tampil karena zoom level
                getPendingTypesText() {
                    if (this.showAllTypesAtAllZooms) return '';
                    if (SHOW_SINGLE_TYPE_ALWAYS && this.selectedJenis.length === 1) return '';

                    const lockedLabels = [];
                    const typesToCheck = [
                        { key: 'puskesmas', label: 'Puskesmas' },
                        { key: 'klinik_utama', label: 'Klinik Utama' },
                        { key: 'klinik_pratama', label: 'Klinik Pratama' },
                        { key: 'laboratorium', label: 'Lab' },
                        { key: 'upkdk', label: 'UPKDK' },
                        { key: 'griya_sehat', label: 'Griya Sehat' },
                        { key: 'tpmd', label: 'TPM' }
                    ];

                    for (const item of typesToCheck) {
                        if (this.selectedJenis.includes(item.key) && !this.isTypeUnlocked(item.key)) {
                            lockedLabels.push(item.label);
                        }
                    }

                    if (lockedLabels.length === 0) return '';
                    if (lockedLabels.length <= 3) {
                        return lockedLabels.join(', ');
                    }
                    return `${lockedLabels.slice(0, 3).join(', ')}, +${lockedLabels.length - 3} lainnya`;
                },

                // Handler toggle switch "Tampilkan semua jenis di semua zoom"
                onToggleAllZooms() {
                    this.syncMarkersForCurrentView(false);
                },

                // Tandatangan filter untuk pengelompokan cache (mengecualikan jenis_faskes)
                getFilterSignature() {
                    const obj = {
                        amb: this.filterAmbulans ? 1 : 0,
                        bpjs: this.filterBpjs ? 1 : 0,
                        rinap: this.filterRawatInap ? 1 : 0,
                        poned: this.filterPoned ? 1 : 0,
                        kec: this.selectedKecamatan || '',
                        q: this.filterSearch.trim(),
                        totamb: this.minTotalAmbulans,
                        gadar: this.minAmbulansGadar,
                        r2: this.minAmbulansRodaDua,
                        bed: this.minBed,
                        sdm: this.minSdm,
                        milik: this.kepemilikan,
                        trs: this.tipeRs,
                        katpkm: this.kategoriPuskesmas,
                        wilpkm: this.wilayahPuskesmas,
                        salinpkm: this.persalinanPuskesmas,
                        ponek: this.ponekRs,
                        katkln: this.kategoriLayananKlinik,
                        bpjskln: this.bpjsKlinik,
                        izin: this.statusIzin,
                        ops: this.statusOperasional,
                        cust: JSON.stringify(this.customFilter || {})
                    };
                    return JSON.stringify(obj);
                },

                // Muat Data Pertama Kali dengan LOD Zoom (Tugas 9)
                loadInitialFaskes() {
                    this.loadFilterCountsAndSync(false);
                },

                // Ambil hitungan per jenis faskes lalu sinkronkan marker view saat ini (Tugas 9)
                loadFilterCountsAndSync(fitBounds = false) {
                    if (this.filterAbortController) {
                        this.filterAbortController.abort();
                    }
                    this.filterAbortController = new AbortController();
                    this.currentRequestId = (this.currentRequestId || 0) + 1;
                    const reqId = this.currentRequestId;

                    this.isFiltering = true;
                    this.loadingStatusText = 'Menghitung data...';

                    // Batalkan request per-jenis yang sedang berjalan sebelumnya
                    this.activeFetchControllers.forEach(ctrl => ctrl.abort());
                    this.activeFetchControllers.clear();

                    if (this.selectedJenis.length === 0) {
                        this.safeClearFaskesLayer();
                        this.faskesList = [];
                        this.totalFilteredCount = 0;
                        this.mountedCount = 0;
                        this.isFiltering = false;
                        this.loadingStatusText = '';
                        return;
                    }

                    const params = this.buildFilterQueryParams();

                    fetch(`{{ url("/api/faskes/markers-count") }}?${params.toString()}`, {
                        signal: this.filterAbortController.signal
                    })
                        .then(res => res.json())
                        .then(res => {
                            if (reqId !== this.currentRequestId) return;
                            this.isFiltering = false;
                            this.loadingStatusText = '';
                            if (!res.success) return;

                            this.typeCounts = res.counts || {};
                            this.totalFilteredCount = res.total || 0;
                            this.totalFaskes = res.total || 0;

                            // Sinkronkan marker untuk jenis faskes yang aktif di zoom saat ini
                            this.syncMarkersForCurrentView(fitBounds);
                        })
                        .catch(err => {
                            if (err.name === 'AbortError') return;
                            if (reqId === this.currentRequestId) {
                                this.isFiltering = false;
                                this.loadingStatusText = '';
                            }
                            console.error('Error fetching markers count:', err);
                        });
                },

                // Sinkronkan data dan DOM marker sesuai viewport & LOD zoom saat ini (Tugas 9)
                async syncMarkersForCurrentView(fitBounds = false) {
                    if (!this.map) return;
                    this.currentZoom = this.map.getZoom();

                    // Tentukan jenis faskes yang aktif / unlocked di zoom saat ini
                    const typesToDisplay = this.selectedJenis.filter(j => this.isTypeUnlocked(j));

                    if (typesToDisplay.length === 0) {
                        // Bersihkan marker kecuali jika ada activeFaskesId dari Analisis Event
                        this.cullMountedMarkers([], fitBounds);
                        this.faskesList = [];
                        return;
                    }

                    // Muat data untuk semua jenis yang unlocked (memakai cache jika sudah ada)
                    const fetchPromises = typesToDisplay.map(jenis => this.fetchTypeData(jenis));
                    const results = await Promise.all(fetchPromises);

                    // Gabungkan seluruh data faskes yang eligible di zoom saat ini
                    let allEligibleItems = [];
                    results.forEach(arr => {
                        if (Array.isArray(arr)) {
                            allEligibleItems = allEligibleItems.concat(arr);
                        }
                    });

                    // Simpan ke faskesList untuk tampilan sidebar "Daftar Faskes Terpilih"
                    this.faskesList = allEligibleItems;

                    // Lakukan Viewport Culling (+ buffer 20%) terhadap data yang eligible
                    this.cullMountedMarkers(allEligibleItems, fitBounds);

                    // Prefetch 1 tingkat zoom berikutnya di background saat idle
                    this.prefetchNextZoomLevel();
                },

                // Ambil data satu jenis faskes dengan caching dan abort handling (Tugas 9)
                fetchTypeData(jenis) {
                    const sig = this.getFilterSignature();
                    const cacheKey = `${sig}__${jenis}`;

                    if (this.typeDataCache.has(cacheKey)) {
                        return Promise.resolve(this.typeDataCache.get(cacheKey));
                    }

                    // Jika jumlah jenis ini 0 berdasarkan markers-count, langsung kembalikan array kosong
                    if (this.typeCounts[jenis] !== undefined && this.typeCounts[jenis] === 0) {
                        this.typeDataCache.set(cacheKey, []);
                        return Promise.resolve([]);
                    }

                    const ctrl = new AbortController();
                    this.activeFetchControllers.set(jenis, ctrl);
                    this.isFetchingType = true;
                    this.loadingStatusText = `Memuat ${this.formatJenis(jenis)}...`;

                    const params = this.buildFilterQueryParams();
                    params.delete('jenis_faskes[]');
                    params.append('jenis_faskes[]', jenis);

                    return fetch(`{{ url("/api/faskes/markers") }}?${params.toString()}`, {
                        signal: ctrl.signal
                    })
                        .then(res => res.json())
                        .then(res => {
                            this.activeFetchControllers.delete(jenis);
                            if (this.activeFetchControllers.size === 0) {
                                this.isFetchingType = false;
                                this.loadingStatusText = '';
                            }
                            if (res.success && Array.isArray(res.data)) {
                                this.typeDataCache.set(cacheKey, res.data);
                                return res.data;
                            }
                            return [];
                        })
                        .catch(err => {
                            this.activeFetchControllers.delete(jenis);
                            if (this.activeFetchControllers.size === 0) {
                                this.isFetchingType = false;
                                this.loadingStatusText = '';
                            }
                            if (err.name === 'AbortError') return [];
                            console.error(`Error fetching markers for ${jenis}:`, err);
                            return [];
                        });
                },

                // Prefetch data jenis faskes untuk level zoom berikutnya saat idle
                prefetchNextZoomLevel() {
                    const nextZoom = (this.currentZoom || BASE_ZOOM) + 1;
                    const nextTypes = this.selectedJenis.filter(j => {
                        const minZ = ZOOM_THRESHOLDS[j] !== undefined ? ZOOM_THRESHOLDS[j] : BASE_ZOOM;
                        return minZ === nextZoom && !this.typeDataCache.has(`${this.getFilterSignature()}__${j}`);
                    });

                    if (nextTypes.length === 0) return;

                    const idleRunner = window.requestIdleCallback || ((cb) => setTimeout(cb, 500));
                    idleRunner(() => {
                        nextTypes.forEach(j => this.fetchTypeData(j));
                    });
                },

                // Viewport Culling dengan buffer 20% dan batch mounting via requestAnimationFrame (Tugas 9)
                cullMountedMarkers(eligibleItems, fitBounds = false) {
                    if (!this.map || !this.faskesLayerGroup) return;

                    // Bounds viewport peta dengan buffer 20% (pad 0.20)
                    const paddedBounds = this.map.getBounds().pad(0.20);
                    const visibleItemsMap = new Map();
                    this.invalidCoordinateCount = 0;

                    eligibleItems.forEach(item => {
                        const rawLat = item.lat !== undefined ? item.lat : item.latitude;
                        const rawLng = item.lng !== undefined ? item.lng : item.longitude;

                        if (rawLat === null || rawLng === null || rawLat === undefined || rawLng === undefined) {
                            this.invalidCoordinateCount++;
                            return;
                        }

                        const lat = parseFloat(rawLat);
                        const lng = parseFloat(rawLng);

                        // Pagar Sanitasi Banyumas
                        if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat < -7.80 || lat > -7.10 || lng < 108.85 || lng > 109.60) {
                            this.invalidCoordinateCount++;
                            return;
                        }

                        // Titik masuk dalam buffer viewport ATAU merupakan titik aktif dari Analisis Event
                        const isInside = paddedBounds.contains([lat, lng]);
                        const isActiveEventFaskes = (this.activeFaskesId && this.activeFaskesId === item.id);

                        if (isInside || isActiveEventFaskes) {
                            visibleItemsMap.set(item.id, { item, lat, lng });
                        }
                    });

                    // 1. UNMOUNT (Hapus marker yang sudah keluar dari viewport)
                    // ATURAN KRUSIAL: Marker dengan popup TERBUKA TIDAK BOLEH dihapus!
                    const toRemoveIds = [];
                    for (const [idStr, marker] of Object.entries(this.markerMap)) {
                        const id = parseInt(idStr) || idStr;
                        if (!visibleItemsMap.has(id)) {
                            // Jangan unmount jika popup sedang terbuka atau ini adalah faskes aktif event
                            if (marker.isPopupOpen && marker.isPopupOpen()) {
                                continue;
                            }
                            if (this.activeFaskesId && this.activeFaskesId === id) {
                                continue;
                            }
                            this.faskesLayerGroup.removeLayer(marker);
                            toRemoveIds.push(id);
                        }
                    }
                    toRemoveIds.forEach(id => delete this.markerMap[id]);

                    // 2. MOUNT (Pasang marker yang baru masuk viewport secara bertahap via rAF)
                    const toMountList = [];
                    visibleItemsMap.forEach(({ item, lat, lng }, id) => {
                        if (!this.markerMap[id]) {
                            toMountList.push({ item, lat, lng, id });
                        }
                    });

                    // Batalkan batch rAF sebelumnya jika ada
                    this.mountBatchId++;
                    const thisBatchId = this.mountBatchId;

                    if (toMountList.length === 0) {
                        this.mountedCount = Object.keys(this.markerMap).length;
                        return;
                    }

                    let index = 0;
                    const chunkSize = 150; // Max 150 marker per frame agar 60fps tetap halus

                    const mountChunk = () => {
                        if (thisBatchId !== this.mountBatchId) return; // Viewport berubah, hentikan chunk usang

                        const slice = toMountList.slice(index, index + chunkSize);
                        slice.forEach(({ item, lat, lng, id }) => {
                            const markerIcon = this.iconCache[item.jenis_faskes] || this.iconCache['default'];
                            const marker = L.marker([lat, lng], { icon: markerIcon });

                            // Bind lazy popup
                            marker.bindPopup(() => this.getFaskesPopupPlaceholder(item), {
                                maxWidth: 280,
                                minWidth: 220,
                                autoPanPadding: [20, 20]
                            });

                            marker.on('popupopen', () => {
                                this.fetchFaskesDetailAndPopulatePopup(marker, item.id);
                            });

                            this.faskesLayerGroup.addLayer(marker);
                            this.markerMap[id] = marker;
                        });

                        index += chunkSize;
                        this.mountedCount = Object.keys(this.markerMap).length;

                        if (index < toMountList.length) {
                            requestAnimationFrame(mountChunk);
                        } else {
                            if (fitBounds && eligibleItems.length > 0) {
                                const b = eligibleItems.map(it => [
                                    parseFloat(it.lat !== undefined ? it.lat : it.latitude),
                                    parseFloat(it.lng !== undefined ? it.lng : it.longitude)
                                ]).filter(([la, lo]) => Number.isFinite(la) && Number.isFinite(lo));
                                if (b.length > 0) {
                                    this.map.fitBounds(b, { padding: [40, 40], animate: false });
                                }
                            }
                        }
                    };

                    requestAnimationFrame(mountChunk);
                },

                // Helper: Bangun URL query string dari seluruh filter aktif
                buildFilterQueryParams() {
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

                    // Filter lanjutan baru (Tugas 4)
                    if (this.minTotalAmbulans > 0) params.append('min_total_ambulans', this.minTotalAmbulans);
                    if (this.minAmbulansGadar > 0) params.append('min_ambulans_gadar', this.minAmbulansGadar);
                    if (this.minAmbulansRodaDua > 0) params.append('min_ambulans_roda_dua', this.minAmbulansRodaDua);
                    if (this.minBed > 0) params.append('min_bed', this.minBed);
                    if (this.minSdm > 0) params.append('min_sdm', this.minSdm);
                    if (this.kepemilikan) params.append('kepemilikan', this.kepemilikan);
                    if (this.tipeRs) params.append('tipe_rs', this.tipeRs);
                    if (this.kategoriPuskesmas) params.append('kategori_puskesmas', this.kategoriPuskesmas);
                    if (this.wilayahPuskesmas) params.append('wilayah_puskesmas', this.wilayahPuskesmas);
                    if (this.persalinanPuskesmas) params.append('persalinan_puskesmas', this.persalinanPuskesmas);
                    if (this.ponekRs) params.append('ponek_rs', this.ponekRs);
                    if (this.kategoriLayananKlinik) params.append('kategori_layanan_klinik', this.kategoriLayananKlinik);
                    if (this.bpjsKlinik) params.append('bpjs_klinik', this.bpjsKlinik);
                    if (this.statusIzin) params.append('status_izin', this.statusIzin);
                    if (this.statusOperasional) params.append('status_operasional', this.statusOperasional);
                    if (this.customFilter) {
                        for (const [k, v] of Object.entries(this.customFilter)) {
                            if (v) params.append(`custom_filter[${k}]`, v);
                        }
                    }

                    return params;
                },

                // AJAX: Ambil data saat filter diubah user
                applyDynamicFilters(fitBounds = false) {
                    this.loadFilterCountsAndSync(fitBounds);
                },

                // Tutup semua popup & bersihkan faskes layer group
                safeClearFaskesLayer() {
                    if (!this.map) return;
                    try {
                        this.map.closePopup();
                    } catch (e) { /* no-op */ }
                    if (this.faskesLayerGroup) {
                        this.faskesLayerGroup.clearLayers();
                    }
                    this.markerMap = {};
                    this.mountedCount = 0;
                },

                // Tutup popup sebelum memindahkan viewport ke faskes terpilih (Tugas 9)
                zoomToFaskes(faskes) {
                    if (!this.map) return;
                    const lat = parseFloat(faskes.lat !== undefined ? faskes.lat : faskes.latitude);
                    const lng = parseFloat(faskes.lng !== undefined ? faskes.lng : faskes.longitude);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                    this.map.closePopup();

                    // Pastikan marker faskes ter-mount
                    const targetZoom = Math.max(this.map.getZoom(), 16);
                    this.map.setView([lat, lng], targetZoom, { animate: false });

                    // Jika marker sudah mounted, buka popup langsung
                    const marker = this.markerMap[faskes.id];
                    if (marker) {
                        marker.openPopup();
                    } else {
                        // Jika belum mounted (misal di luar view sebelum zoom), buat dan mount marker langsung
                        const markerIcon = this.iconCache[faskes.jenis_faskes] || this.iconCache['default'];
                        const newMarker = L.marker([lat, lng], { icon: markerIcon });
                        newMarker.bindPopup(() => this.getFaskesPopupPlaceholder(faskes), {
                            maxWidth: 280,
                            minWidth: 220,
                            autoPanPadding: [20, 20]
                        });
                        newMarker.on('popupopen', () => {
                            this.fetchFaskesDetailAndPopulatePopup(newMarker, faskes.id);
                        });
                        this.faskesLayerGroup.addLayer(newMarker);
                        this.markerMap[faskes.id] = newMarker;
                        this.mountedCount = Object.keys(this.markerMap).length;
                        newMarker.openPopup();
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

                    this.minTotalAmbulans = 0;
                    this.minAmbulansGadar = 0;
                    this.minAmbulansRodaDua = 0;
                    this.minBed = 0;
                    this.minSdm = 0;
                    this.kepemilikan = '';
                    this.tipeRs = '';
                    this.kategoriPuskesmas = '';
                    this.wilayahPuskesmas = '';
                    this.persalinanPuskesmas = '';
                    this.ponekRs = '';
                    this.kategoriLayananKlinik = '';
                    this.bpjsKlinik = '';
                    this.statusIzin = '';
                    this.statusOperasional = 'aktif';
                    this.customFilter = {};

                    this.applyDynamicFilters(true);
                },

                // Update kontras warna garis batas wilayah sesuai mode basemap (Tugas 7B)
                updateBoundaryContrast(layerName) {
                    this.currentBasemapName = layerName;
                    if (!this.kabupatenGeoJsonLayer) return;
                    const isSatellite = (layerName === 'Satelit' || layerName === 'Satelit + Label');
                    this.kabupatenGeoJsonLayer.setStyle({
                        color: isSatellite ? '#facc15' : '#1d4ed8',
                        weight: isSatellite ? 3 : 2.5,
                        fill: false,
                        interactive: false
                    });
                },

                // Muat Batas Luar Kabupaten Banyumas (Outer Boundary Only & Non-Interaktif)
                loadKabupatenBoundary() {
                    fetch('{{ asset("geojson/banyumas-kabupaten-outer.json") }}')
                        .then(res => {
                            if (!res.ok) throw new Error('Gagal memuat boundary kabupaten: ' + res.status);
                            return res.json();
                        })
                        .then(geojsonData => {
                            const isSatellite = (this.currentBasemapName === 'Satelit' || this.currentBasemapName === 'Satelit + Label');
                            this.kabupatenGeoJsonLayer = L.geoJSON(geojsonData, {
                                interactive: false,
                                bubblingMouseEvents: false,
                                style: {
                                    color: isSatellite ? '#facc15' : '#1d4ed8',
                                    weight: isSatellite ? 3 : 2.5,
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

                    // Sertakan filter aktif jenis & spesifik lama
                    if (this.selectedJenis.length > 0 && this.selectedJenis.length < 11) {
                        this.selectedJenis.forEach(j => params.append('jenis_faskes[]', j));
                    }
                    if (this.filterAmbulans) params.append('has_ambulans', '1');
                    if (this.filterBpjs) params.append('has_bpjs', '1');
                    if (this.filterRawatInap) params.append('has_rawat_inap', '1');
                    if (this.filterPoned) params.append('has_poned', '1');

                    // Sertakan filter atribut lanjutan (Tugas 4)
                    if (this.minTotalAmbulans > 0) params.append('min_total_ambulans', this.minTotalAmbulans);
                    if (this.minAmbulansGadar > 0) params.append('min_ambulans_gadar', this.minAmbulansGadar);
                    if (this.minAmbulansRodaDua > 0) params.append('min_ambulans_roda_dua', this.minAmbulansRodaDua);
                    if (this.minBed > 0) params.append('min_bed', this.minBed);
                    if (this.minSdm > 0) params.append('min_sdm', this.minSdm);
                    if (this.kepemilikan) params.append('kepemilikan', this.kepemilikan);
                    if (this.tipeRs) params.append('tipe_rs', this.tipeRs);
                    if (this.kategoriPuskesmas) params.append('kategori_puskesmas', this.kategoriPuskesmas);
                    if (this.wilayahPuskesmas) params.append('wilayah_puskesmas', this.wilayahPuskesmas);
                    if (this.persalinanPuskesmas) params.append('persalinan_puskesmas', this.persalinanPuskesmas);
                    if (this.ponekRs) params.append('ponek_rs', this.ponekRs);
                    if (this.kategoriLayananKlinik) params.append('kategori_layanan_klinik', this.kategoriLayananKlinik);
                    if (this.bpjsKlinik) params.append('bpjs_klinik', this.bpjsKlinik);
                    if (this.statusIzin) params.append('status_izin', this.statusIzin);
                    if (this.customFilter) {
                        for (const [k, v] of Object.entries(this.customFilter)) {
                            if (v) params.append(`custom_filter[${k}]`, v);
                        }
                    }

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

                // Fokus ke Salah Satu Rute Faskes (Tugas 8B & 9)
                fokusKeRute(item, index) {
                    if (!this.map) return;
                    this.map.closePopup();
                    this.activeFaskesId = item.detail_faskes.id;
                    const faskes = item.detail_faskes;

                    if (item.titik_rute_jalan) {
                        const tempLayer = L.geoJSON(item.titik_rute_jalan);
                        this.map.fitBounds(tempLayer.getBounds(), { padding: [60, 60], animate: false });
                    } else {
                        const lat = parseFloat(faskes.lat !== undefined ? faskes.lat : faskes.latitude);
                        const lng = parseFloat(faskes.lng !== undefined ? faskes.lng : faskes.longitude);
                        if (Number.isFinite(lat) && Number.isFinite(lng)) {
                            this.map.setView([lat, lng], 15, { animate: false });
                        }
                    }

                    // Pastikan marker faskes ini ter-mount (pengecualian LOD Analisis Event)
                    let marker = this.markerMap[faskes.id];
                    if (!marker) {
                        const lat = parseFloat(faskes.lat !== undefined ? faskes.lat : faskes.latitude);
                        const lng = parseFloat(faskes.lng !== undefined ? faskes.lng : faskes.longitude);
                        if (Number.isFinite(lat) && Number.isFinite(lng)) {
                            const markerIcon = this.iconCache[faskes.jenis_faskes] || this.iconCache['default'];
                            marker = L.marker([lat, lng], { icon: markerIcon });
                            marker.bindPopup(() => this.getFaskesPopupPlaceholder(faskes), {
                                maxWidth: 280,
                                minWidth: 220,
                                autoPanPadding: [20, 20]
                            });
                            marker.on('popupopen', () => {
                                this.fetchFaskesDetailAndPopulatePopup(marker, faskes.id);
                            });
                            this.faskesLayerGroup.addLayer(marker);
                            this.markerMap[faskes.id] = marker;
                            this.mountedCount = Object.keys(this.markerMap).length;
                        }
                    }

                    if (marker) {
                        marker.openPopup();
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

                    // Filter Lanjutan (Tugas 4)
                    if (this.minTotalAmbulans > 0) params.append('min_total_ambulans', this.minTotalAmbulans);
                    if (this.minAmbulansGadar > 0) params.append('min_ambulans_gadar', this.minAmbulansGadar);
                    if (this.minAmbulansRodaDua > 0) params.append('min_ambulans_roda_dua', this.minAmbulansRodaDua);
                    if (this.minBed > 0) params.append('min_bed', this.minBed);
                    if (this.minSdm > 0) params.append('min_sdm', this.minSdm);
                    if (this.kepemilikan) params.append('kepemilikan', this.kepemilikan);
                    if (this.tipeRs) params.append('tipe_rs', this.tipeRs);
                    if (this.kategoriPuskesmas) params.append('kategori_puskesmas', this.kategoriPuskesmas);
                    if (this.wilayahPuskesmas) params.append('wilayah_puskesmas', this.wilayahPuskesmas);
                    if (this.persalinanPuskesmas) params.append('persalinan_puskesmas', this.persalinanPuskesmas);
                    if (this.ponekRs) params.append('ponek_rs', this.ponekRs);
                    if (this.kategoriLayananKlinik) params.append('kategori_layanan_klinik', this.kategoriLayananKlinik);
                    if (this.bpjsKlinik) params.append('bpjs_klinik', this.bpjsKlinik);
                    if (this.statusIzin) params.append('status_izin', this.statusIzin);
                    if (this.customFilter) {
                        for (const [k, v] of Object.entries(this.customFilter)) {
                            if (v) params.append(`custom_filter[${k}]`, v);
                        }
                    }

                    return `${base}?${params.toString()}`;
                },

                // Inisialisasi Objek L.divIcon Satu Kali per Jenis Faskes (Tugas 8B & 8C)
                initIconCache() {
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

                    this.iconCache = {};
                    for (const [jenis, cfg] of Object.entries(iconConfig)) {
                        this.iconCache[jenis] = this.createFaskesDivIcon(cfg.color, cfg.label);
                    }
                    this.iconCache['default'] = this.createFaskesDivIcon('#64748b', 'F');
                },

                // Generator SVG Pin Marker Bebas Distorsi Rotasi & Terpasang Presisi di Koordinat (Tugas 8C)
                createFaskesDivIcon(color, label) {
                    return L.divIcon({
                        className: 'custom-faskes-marker',
                        html: `
                            <div style="width: 30px; height: 38px; pointer-events: auto;">
                                <svg width="30" height="38" viewBox="0 0 30 38" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block; filter: drop-shadow(0 3px 5px rgba(0,0,0,0.35));">
                                    <path d="M15 0C6.716 0 0 6.716 0 15c0 10.2 15 23 15 23s15-12.8 15-23c0-8.284-6.716-15-15-15z" fill="${color}" stroke="#ffffff" stroke-width="2"/>
                                    <text x="15" y="15.5" text-anchor="middle" dominant-baseline="central" fill="#ffffff" font-size="10" font-weight="800" font-family="system-ui, -apple-system, sans-serif" letter-spacing="-0.5px">${label}</text>
                                </svg>
                            </div>
                        `,
                        iconSize: [30, 38],
                        iconAnchor: [15, 38],
                        popupAnchor: [0, -38]
                    });
                },

                // Helper kompatibilitas untuk mendapatkan icon faskes
                createFaskesIcon(jenis) {
                    if (!this.iconCache || Object.keys(this.iconCache).length === 0) {
                        this.initIconCache();
                    }
                    return this.iconCache[jenis] || this.iconCache['default'];
                },

                // Placeholder popup ringan saat marker diklik pertama kali (Tugas 8B)
                getFaskesPopupPlaceholder(item) {
                    if (this.faskesDetailCache.has(item.id)) {
                        return this.createFaskesPopupHtml(this.faskesDetailCache.get(item.id));
                    }

                    const jenisText = this.formatJenis(item.jenis_faskes);
                    const icon = (name, cls = 'w-3 h-3') =>
                        `<svg class="icon ${cls} inline-block align-[-2px]"><use href="#${name}"/></svg>`;

                    const lat = item.lat !== undefined ? item.lat : item.latitude;
                    const lng = item.lng !== undefined ? item.lng : item.longitude;

                    return `
                        <div class="text-xs p-1 max-w-[240px]">
                            <div class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 mb-1">
                                ${jenisText}
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm leading-tight">${item.nama}</h4>
                            <p class="text-slate-500 text-[11px] mt-1">${item.kecamatan ? 'Kec. ' + item.kecamatan : '-'}</p>
                            <div class="my-2 py-1 px-2 rounded bg-blue-50/80 text-blue-600 text-[11px] font-medium flex items-center justify-center gap-1.5 border border-blue-100">
                                <svg class="animate-spin h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Memuat detail spesifikasi...</span>
                            </div>
                            <hr class="my-2 border-slate-100">
                            <button onclick="window.dispatchEvent(new CustomEvent('set-event-here', {detail: {lat: ${lat}, lng: ${lng}}}))"
                                    class="w-full text-center py-1 rounded bg-blue-50 text-blue-600 hover:bg-blue-100 font-semibold text-[11px] transition flex items-center justify-center gap-1">
                                ${icon('ic-pin', 'w-3.5 h-3.5')} Jadikan Titik Event
                            </button>
                        </div>
                    `;
                },

                // Ambil detail faskes on-demand dari endpoint /api/faskes/{id} dan cache hasilnya (Tugas 8A & 8B)
                fetchFaskesDetailAndPopulatePopup(marker, id) {
                    if (this.faskesDetailCache.has(id)) {
                        const cached = this.faskesDetailCache.get(id);
                        if (marker.isPopupOpen()) {
                            marker.setPopupContent(this.createFaskesPopupHtml(cached));
                        }
                        return;
                    }

                    fetch(`{{ url('/api/faskes') }}/${id}`)
                        .then(res => res.json())
                        .then(res => {
                            if (!res.success || !res.data) return;
                            const fullFaskes = res.data.faskes || res.data;
                            if (res.data.detail) {
                                fullFaskes.detail = res.data.detail;
                            }
                            this.faskesDetailCache.set(id, fullFaskes);
                            if (marker.isPopupOpen()) {
                                marker.setPopupContent(this.createFaskesPopupHtml(fullFaskes));
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching detail faskes id ' + id + ':', err);
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
                    if (detail.masa_izin) {
                        badgeHtml += `<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-medium bg-slate-100 text-slate-700 mr-1">Izin: ${detail.masa_izin}</span>`;
                    }

                    if (Array.isArray(faskes.field_values) && faskes.field_values.length > 0) {
                        faskes.field_values.forEach(fv => {
                            if (fv.definition && fv.value) {
                                badgeHtml += `<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 mr-1">${fv.definition.label}: ${fv.value}</span>`;
                            }
                        });
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
                            <button onclick="window.dispatchEvent(new CustomEvent('set-event-here', {detail: {lat: ${faskes.latitude !== undefined ? faskes.latitude : faskes.lat}, lng: ${faskes.longitude !== undefined ? faskes.longitude : faskes.lng}}}))"
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