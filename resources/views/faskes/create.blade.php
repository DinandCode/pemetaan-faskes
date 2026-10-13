<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Faskes Baru - Kab. Banyumas</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ url('/js/faskes-basemaps.js') }}"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        #mini-map {
            position: relative;
            z-index: 0;
        }

        header.sticky {
            z-index: 1000 !important;
        }

        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system,
                         BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .form-card {
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .form-card:hover {
            border-color: #cbd5e1;
        }

        .input-modern {
            transition: all .2s ease;
        }

        .input-modern:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        .section-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            flex-shrink: 0;
        }

        .leaflet-container {
            font-family: inherit;
        }

        .leaflet-control-zoom a {
            color: #334155 !important;
        }

        .leaflet-control-attribution {
            font-size: 9px !important;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col"
      x-data="faskesFormApp({
          initialJenis: '{{ old('jenis_faskes', 'puskesmas') }}',
          initialLat: {{ old('latitude', -7.424364) }},
          initialLng: {{ old('longitude', 109.230345) }}
      })"
      x-init="initMiniMap()">

    <!-- Header Navigation -->
    <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-3.5 sticky top-0 z-30 shadow-sm">
        <div class="max-w-6xl mx-auto flex items-center justify-between">

            <div class="flex items-center gap-3">
                <a href="{{ route('faskes.index') }}"
                   class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200
                          text-slate-600 flex items-center justify-center transition"
                   title="Kembali">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>

                <div>
                    <h1 class="text-sm sm:text-base font-bold text-slate-800 leading-tight">
                        Tambah Fasilitas Kesehatan Baru
                    </h1>

                    <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5">
                        Formulir Pendaftaran & Penentuan Koordinat Spasial PostGIS
                    </p>
                </div>
            </div>

            <a href="{{ route('faskes.index') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold
                      text-slate-500 hover:text-slate-800 transition">
                <i class="fa-solid fa-xmark text-[11px]"></i>
                Batal
            </a>
        </div>
    </header>


    <!-- Main Container -->
    <main class="flex-1 max-w-6xl w-full mx-auto p-4 sm:p-6">

        @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700
                        px-4 py-3 rounded-xl text-xs shadow-sm">

                <div class="flex items-start gap-2">
                    <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-circle-exclamation text-red-600 text-xs"></i>
                    </div>

                    <div>
                        <div class="font-bold mb-1">
                            Terdapat kesalahan input:
                        </div>

                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

 @include('faskes._alert') 
        <form action="{{ route('faskes.store') }}" method="POST" class="space-y-5">
            @csrf


            <!-- SECTION 1: DATA UTAMA & KOORDINAT SPASIAL -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">


                <!-- Left Column: Data Pokok Faskes -->
                <div class="lg:col-span-7 bg-white p-5 rounded-2xl
                            border border-slate-200 shadow-sm form-card space-y-4">

                    <!-- Section Header -->
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-3">

                        <div class="section-icon bg-blue-50 text-blue-600">
                            <i class="fa-solid fa-hospital text-sm"></i>
                        </div>

                        <div>
                            <h2 class="text-sm font-bold text-slate-800">
                                Informasi Pokok Faskes
                            </h2>

                            <p class="text-[10px] text-slate-400 mt-0.5">
                                Informasi dasar fasilitas kesehatan
                            </p>
                        </div>
                    </div>


                    <!-- Nama Faskes -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Nama Fasilitas Kesehatan
                            <span class="text-red-500">*</span>
                        </label>

                        <input type="text"
                               name="nama"
                               value="{{ old('nama') }}"
                               required
                               placeholder="Contoh: RSUD Banyumas, Puskesmas Sokaraja 1"
                               class="input-modern w-full bg-slate-50 border border-slate-200
                                      rounded-lg px-3 py-2.5 text-xs text-slate-800
                                      placeholder:text-slate-400
                                      focus:bg-white focus:outline-none focus:ring-2
                                      focus:ring-blue-500/20 focus:border-blue-500">
                    </div>


                    <!-- Jenis Faskes & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Jenis Faskes
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <i class="fa-solid fa-layer-group absolute left-3 top-1/2
                                          -translate-y-1/2 text-slate-400 text-xs"></i>

                                <select name="jenis_faskes"
                                        x-model="jenisFaskes"
                                        required
                                        class="input-modern appearance-none w-full bg-slate-50
                                               border border-slate-200 rounded-lg
                                               pl-9 pr-8 py-2.5 text-xs font-semibold
                                               text-slate-800 focus:bg-white focus:outline-none
                                               focus:ring-2 focus:ring-blue-500/20
                                               focus:border-blue-500">

                                    <option value="rumah_sakit">
                                        Rumah Sakit
                                    </option>

                                    <option value="puskesmas">
                                        Puskesmas
                                    </option>

                                    <option value="klinik_pratama">
                                        Klinik Pratama
                                    </option>

                                    <option value="klinik_utama">
                                        Klinik Utama
                                    </option>

                                    <option value="laboratorium">
                                        Laboratorium
                                    </option>

                                    <option value="upkdk">
                                        UPKDK (Pustu / PKD)
                                    </option>

                                    <option value="griya_sehat">
                                        Griya Sehat
                                    </option>

                                    <option value="tpmd">
                                        TPMD (Praktik Mandiri Dokter)
                                    </option>

                                    <option value="tpmdg">
                                        TPMDG (Praktik Mandiri Dokter Gigi)
                                    </option>

                                    <option value="tpmb">
                                        TPMB (Praktik Mandiri Bidan)
                                    </option>

                                    <option value="tpmp">
                                        TPMP (Praktik Mandiri Perawat)
                                    </option>

                                </select>

                                <i class="fa-solid fa-chevron-down absolute right-3 top-1/2
                                          -translate-y-1/2 text-slate-400 text-[9px]
                                          pointer-events-none"></i>
                            </div>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Status Operasional
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <i class="fa-solid fa-circle-check absolute left-3 top-1/2
                                          -translate-y-1/2 text-slate-400 text-xs"></i>

                                <select name="status"
                                        required
                                        class="input-modern appearance-none w-full bg-slate-50
                                               border border-slate-200 rounded-lg
                                               pl-9 pr-8 py-2.5 text-xs font-semibold
                                               text-slate-800 focus:bg-white focus:outline-none
                                               focus:ring-2 focus:ring-blue-500/20
                                               focus:border-blue-500">

                                    <option value="aktif"
                                            {{ old('status', 'aktif') == 'aktif' ? 'selected' : '' }}>
                                        Aktif Melayani
                                    </option>

                                    <option value="nonaktif"
                                            {{ old('status') == 'nonaktif' ? 'selected' : '' }}>
                                        Nonaktif / Tutup
                                    </option>

                                </select>

                                <i class="fa-solid fa-chevron-down absolute right-3 top-1/2
                                          -translate-y-1/2 text-slate-400 text-[9px]
                                          pointer-events-none"></i>
                            </div>
                        </div>

                    </div>


                    <!-- Alamat Lengkap -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Alamat Lengkap
                        </label>

                        <textarea name="alamat"
                                  rows="2"
                                  x-ref="alamatInput"
                                  placeholder="Nama jalan, nomor gedung, RT/RW..."
                                  class="input-modern w-full bg-slate-50 border border-slate-200
                                         rounded-lg px-3 py-2.5 text-xs text-slate-800
                                         placeholder:text-slate-400 resize-none
                                         focus:bg-white focus:outline-none
                                         focus:ring-2 focus:ring-blue-500/20
                                         focus:border-blue-500">{{ old('alamat') }}</textarea>
                    </div>


                    <!-- Wilayah Administratif -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kecamatan
                            </label>

                            <div class="relative">
                                <i class="fa-solid fa-map-location-dot absolute left-3 top-1/2
                                          -translate-y-1/2 text-slate-400 text-xs"></i>

                                <input type="text"
                                       name="kecamatan"
                                       value="{{ old('kecamatan') }}"
                                       placeholder="Contoh: Purwokerto Timur, Sokaraja, Ajibarang"
                                       class="input-modern w-full bg-slate-50
                                              border border-slate-200 rounded-lg
                                              pl-9 pr-3 py-2.5 text-xs text-slate-800
                                              placeholder:text-slate-400
                                              focus:bg-white focus:outline-none
                                              focus:ring-2 focus:ring-blue-500/20
                                              focus:border-blue-500">
                            </div>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Desa / Kelurahan
                            </label>

                            <div class="relative">
                                <i class="fa-solid fa-location-dot absolute left-3 top-1/2
                                          -translate-y-1/2 text-slate-400 text-xs"></i>

                                <input type="text"
                                       name="desa"
                                       value="{{ old('desa') }}"
                                       placeholder="Contoh: Mersi, Sudagaran, Rempoah"
                                       class="input-modern w-full bg-slate-50
                                              border border-slate-200 rounded-lg
                                              pl-9 pr-3 py-2.5 text-xs text-slate-800
                                              placeholder:text-slate-400
                                              focus:bg-white focus:outline-none
                                              focus:ring-2 focus:ring-blue-500/20
                                              focus:border-blue-500">
                            </div>
                        </div>

                    </div>


                    <!-- Nomor Telepon -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Nomor Telepon / Kontak IGD
                        </label>

                        <div class="relative">
                            <i class="fa-solid fa-phone absolute left-3 top-1/2
                                      -translate-y-1/2 text-slate-400 text-xs"></i>

                            <input type="text"
                                   name="nomor_telepon"
                                   value="{{ old('nomor_telepon') }}"
                                   placeholder="Contoh: 0281-632708"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          pl-9 pr-3 py-2.5 text-xs text-slate-800
                                          placeholder:text-slate-400
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">
                        </div>
                    </div>

                </div>


                <!-- Right Column: Mini Map -->
                <div class="lg:col-span-5 bg-white p-5 rounded-2xl
                            border border-slate-200 shadow-sm form-card
                            flex flex-col space-y-3">

                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">

                        <div class="flex items-center gap-3">

                            <div class="section-icon bg-emerald-50 text-emerald-600">
                                <i class="fa-solid fa-location-crosshairs text-sm"></i>
                            </div>

                            <div>
                                <h2 class="text-sm font-bold text-slate-800">
                                    Titik Koordinat Spasial
                                </h2>

                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    Tentukan posisi fasilitas
                                </p>
                            </div>

                        </div>

                        <span class="text-[9px] bg-blue-50 text-blue-700
                                     border border-blue-100 px-2 py-1
                                     rounded-md font-bold">
                            PostGIS SRID 4326
                        </span>

                    </div>


                    <!-- Pencarian Alamat -->
                    <div class="relative">

                        <label class="block text-[11px] font-semibold text-slate-600 mb-1.5">
                            Cari Alamat
                            <span class="font-normal text-slate-400">
                                (opsional — bantu isi koordinat otomatis)
                            </span>
                        </label>

                        <div class="relative">

                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2
                                      -translate-y-1/2 text-slate-400 text-xs"></i>

                            <input type="text"
                                   x-model="addressQuery"
                                   @input="onAddressInput()"
                                   @keydown.escape="clearAddressResults()"
                                   placeholder="Ketik alamat, contoh: Jl. Gerilya, Purwokerto"
                                   autocomplete="off"
                                   class="input-modern w-full bg-slate-50 border border-slate-200
                                          rounded-lg pl-9 pr-9 py-2.5 text-xs text-slate-800
                                          placeholder:text-slate-400
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">


                            <!-- Spinner -->
                            <template x-if="isSearchingAddress">

                                <svg class="animate-spin h-3.5 w-3.5 text-blue-500
                                            absolute right-3 top-1/2 -translate-y-1/2"
                                     fill="none"
                                     viewBox="0 0 24 24">

                                    <circle class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4">
                                    </circle>

                                    <path class="opacity-75"
                                          fill="currentColor"
                                          d="M4 12a8 8 0 018-8v8H4z">
                                    </path>

                                </svg>

                            </template>


                            <!-- Tombol clear -->
                            <template x-if="!isSearchingAddress && addressQuery">

                                <button type="button"
                                        @click="addressQuery = ''; clearAddressResults()"
                                        class="absolute right-2.5 top-1/2
                                               -translate-y-1/2 w-5 h-5 rounded-full
                                               bg-slate-200 hover:bg-slate-300
                                               text-slate-500 flex items-center
                                               justify-center transition">

                                    <i class="fa-solid fa-xmark text-[9px]"></i>

                                </button>

                            </template>

                        </div>


                        <!-- Dropdown Hasil Pencarian -->
                        <template x-if="addressResults.length > 0">

                            <div class="absolute z-[600] mt-1 w-full bg-white
                                        border border-slate-200 rounded-xl
                                        shadow-xl max-h-56 overflow-y-auto">

                                <template x-for="(result, idx) in addressResults"
                                          :key="idx">

                                    <button type="button"
                                            @click="selectAddressResult(result)"
                                            class="w-full text-left px-3 py-2.5
                                                   text-[11px] text-slate-700
                                                   hover:bg-blue-50 border-b
                                                   border-slate-100 last:border-b-0
                                                   transition flex items-start gap-2">

                                        <i class="fa-solid fa-location-dot
                                                  text-blue-500 mt-0.5 shrink-0"></i>

                                        <span x-text="result.display_name"></span>

                                    </button>

                                </template>

                            </div>

                        </template>


                        <!-- Info Tidak Ditemukan -->
                        <template x-if="hasSearchedAddress &&
                                         !isSearchingAddress &&
                                         addressResults.length === 0">

                            <div class="mt-1 text-[11px] text-amber-700
                                        bg-amber-50 border border-amber-200
                                        rounded-lg px-2.5 py-2 flex items-start gap-2">

                                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>

                                <span>
                                    Alamat tidak ditemukan. Tandai titik lokasi
                                    secara manual lewat peta di bawah.
                                </span>

                            </div>

                        </template>


                        <!-- Error -->
                        <template x-if="addressSearchError">

                            <div class="mt-1 text-[11px] text-red-600
                                        bg-red-50 border border-red-200
                                        rounded-lg px-2.5 py-2 flex items-start gap-2">

                                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>

                                <span x-text="addressSearchError"></span>

                            </div>

                        </template>

                    </div>


                    <div class="flex items-center gap-2 text-[10px] text-slate-400">

                        <span class="flex-1 border-t border-slate-200"></span>

                        <span class="px-1">
                            atau tandai manual
                        </span>

                        <span class="flex-1 border-t border-slate-200"></span>

                    </div>


                    <!-- Mini Map Container -->
                    <div class="relative flex-1 min-h-[220px]
                                rounded-xl overflow-hidden
                                border border-slate-200 shadow-inner isolate">

                        <div id="mini-map" class="h-full w-full"></div>

                        <div class="absolute bottom-2 left-2
                                    bg-white/95 backdrop-blur-sm
                                    px-2.5 py-1.5 rounded-lg shadow
                                    border border-slate-200
                                    text-[10px] text-slate-600
                                    z-[500] flex items-center gap-1.5">

                            <i class="fa-solid fa-hand-pointer text-blue-500"></i>

                            <span>
                                Klik peta atau geser marker
                            </span>

                        </div>

                    </div>


                    <!-- Input Latitude & Longitude -->
                    <div class="grid grid-cols-2 gap-2">

                        <div>
                            <label class="block text-[11px] font-semibold
                                          text-slate-600 mb-1">

                                Latitude (Lintang)
                                <span class="text-red-500">*</span>

                            </label>

                            <div class="relative">

                                <i class="fa-solid fa-arrows-up-down absolute
                                          left-2.5 top-1/2 -translate-y-1/2
                                          text-slate-400 text-[10px]"></i>

                                <input type="number"
                                       step="any"
                                       name="latitude"
                                       x-model.number="lat"
                                       @input="onManualCoordinateChange()"
                                       required
                                       class="input-modern w-full bg-slate-50
                                              border border-slate-200
                                              rounded-lg pl-7 pr-2.5 py-2
                                              text-xs font-mono text-slate-800
                                              focus:bg-white focus:outline-none
                                              focus:ring-2 focus:ring-blue-500/20
                                              focus:border-blue-500">

                            </div>
                        </div>


                        <div>
                            <label class="block text-[11px] font-semibold
                                          text-slate-600 mb-1">

                                Longitude (Bujur)
                                <span class="text-red-500">*</span>

                            </label>

                            <div class="relative">

                                <i class="fa-solid fa-arrows-left-right absolute
                                          left-2.5 top-1/2 -translate-y-1/2
                                          text-slate-400 text-[10px]"></i>

                                <input type="number"
                                       step="any"
                                       name="longitude"
                                       x-model.number="lng"
                                       @input="onManualCoordinateChange()"
                                       required
                                       class="input-modern w-full bg-slate-50
                                              border border-slate-200
                                              rounded-lg pl-7 pr-2.5 py-2
                                              text-xs font-mono text-slate-800
                                              focus:bg-white focus:outline-none
                                              focus:ring-2 focus:ring-blue-500/20
                                              focus:border-blue-500">

                            </div>
                        </div>

                    </div>


                    <!-- Geocode Info -->
                    <template x-if="lastSourceIsGeocode">

                        <div class="text-[10px] text-slate-500
                                    bg-slate-50 border border-slate-200
                                    rounded-lg px-2.5 py-2
                                    flex items-start gap-2">

                            <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>

                            <span>
                                Koordinat ini hasil pencarian alamat otomatis,
                                mungkin tidak 100% presisi. Cek posisi pin
                                di peta — geser jika perlu.
                            </span>

                        </div>

                    </template>


                    <!-- GPS -->
                    <button type="button"
                            @click="getCurrentLocation()"
                            class="w-full py-2 px-3
                                   bg-slate-100 hover:bg-blue-50
                                   border border-slate-200 hover:border-blue-200
                                   text-slate-700 hover:text-blue-700
                                   rounded-lg text-xs font-semibold
                                   flex items-center justify-center gap-2
                                   transition">

                        <i class="fa-solid fa-crosshairs text-xs"></i>

                        <span>
                            Deteksi Lokasi GPS Saya
                        </span>

                    </button>

                </div>

            </div>


            <!-- SECTION 2 -->
            <div class="bg-white p-5 rounded-2xl
                        border border-slate-200 shadow-sm form-card space-y-4">

                <div class="flex flex-col sm:flex-row sm:items-center
                            justify-between gap-3 border-b border-slate-100 pb-3">

                    <div class="flex items-center gap-3">

                        <div class="section-icon bg-violet-50 text-violet-600">
                            <i class="fa-solid fa-sliders text-sm"></i>
                        </div>

                        <div>
                            <h2 class="text-sm font-bold text-slate-800">
                                Spesifikasi & Detail Layanan
                            </h2>

                            <p class="text-[10px] text-slate-400 mt-0.5">
                                Detail layanan berdasarkan jenis fasilitas
                            </p>
                        </div>

                        <span class="text-[10px] font-semibold px-2.5 py-1
                                     rounded-md bg-blue-50 text-blue-700
                                     border border-blue-100 capitalize"
                              x-text="jenisFaskes.replace('_', ' ')">
                        </span>

                    </div>

                    <span class="text-[10px] text-slate-400">
                        Field child menyesuaikan jenis faskes
                    </span>

                </div>


                <!-- 1. CHILD: PUSKESMAS -->
                <div x-show="jenisFaskes === 'puskesmas'"
                     x-transition
                     class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kategori Pelayanan
                            </label>

                            <select name="puskesmas_kategori"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs
                                           focus:bg-white focus:outline-none
                                           focus:ring-2 focus:ring-blue-500/20
                                           focus:border-blue-500">

                                <option value="rawat_jalan"
                                        {{ old('puskesmas_kategori') == 'rawat_jalan' ? 'selected' : '' }}>
                                    Rawat Jalan
                                </option>

                                <option value="rawat_inap"
                                        {{ old('puskesmas_kategori', 'rawat_inap') == 'rawat_inap' ? 'selected' : '' }}>
                                    Rawat Inap
                                </option>

                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Klasifikasi Wilayah
                            </label>

                            <select name="puskesmas_wilayah"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs
                                           focus:bg-white focus:outline-none
                                           focus:ring-2 focus:ring-blue-500/20
                                           focus:border-blue-500">

                                <option value="perkotaan"
                                        {{ old('puskesmas_wilayah', 'perkotaan') == 'perkotaan' ? 'selected' : '' }}>
                                    Perkotaan
                                </option>

                                <option value="pedesaan"
                                        {{ old('puskesmas_wilayah') == 'pedesaan' ? 'selected' : '' }}>
                                    Perdesaan
                                </option>

                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Masa Berlaku Izin Operasional
                            </label>

                            <input type="date"
                                   name="puskesmas_masa_izin"
                                   value="{{ old('puskesmas_masa_izin') }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">
                        </div>

                    </div>


                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Jumlah TT Rawat Inap
                            </label>

                            <input type="number"
                                   min="0"
                                   name="puskesmas_jumlah_tempat_tidur"
                                   value="{{ old('puskesmas_jumlah_tempat_tidur', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Jumlah Tenaga Medis (SDM)
                            </label>

                            <input type="number"
                                   min="0"
                                   name="puskesmas_jumlah_sdm"
                                   value="{{ old('puskesmas_jumlah_sdm', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">
                        </div>

                    </div>


                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kemampuan Pelayanan Persalinan
                            </label>

                            <select name="puskesmas_kemampuan_persalinan"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs
                                           focus:bg-white focus:outline-none
                                           focus:ring-2 focus:ring-blue-500/20
                                           focus:border-blue-500">

                                <option value="PONED"
                                        {{ old('puskesmas_kemampuan_persalinan') == 'PONED' ? 'selected' : '' }}>
                                    PONED
                                </option>

                                <option value="NON PONED (Mampu Salin)"
                                        {{ old('puskesmas_kemampuan_persalinan', 'NON PONED (Mampu Salin)') == 'NON PONED (Mampu Salin)' ? 'selected' : '' }}>
                                    NON PONED (Mampu Salin)
                                </option>

                                <option value="NON PONED (Tidak Mampu Salin)"
                                        {{ old('puskesmas_kemampuan_persalinan') == 'NON PONED (Tidak Mampu Salin)' ? 'selected' : '' }}>
                                    NON PONED (Tidak Mampu Salin)
                                </option>

                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Ambulans Transport (R4)
                            </label>

                            <input type="number"
                                   min="0"
                                   name="puskesmas_ambulans_transport"
                                   value="{{ old('puskesmas_ambulans_transport', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500"
                                   placeholder="Jumlah unit">
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Ambulans Motor (R2)
                            </label>

                            <input type="number"
                                   min="0"
                                   name="puskesmas_ambulans_roda_dua"
                                   value="{{ old('puskesmas_ambulans_roda_dua', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500"
                                   placeholder="Jumlah unit">
                        </div>

                    </div>

                </div>


                <!-- 2. CHILD: RUMAH SAKIT -->
                <div x-show="jenisFaskes === 'rumah_sakit'"
                     x-transition
                     class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Tipe Rumah Sakit
                            </label>

                            <select name="rs_tipe_rs"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs
                                           focus:bg-white focus:outline-none
                                           focus:ring-2 focus:ring-blue-500/20
                                           focus:border-blue-500">
                                <option value="">-- Pilih Tipe RS --</option>
                                <option value="A" {{ old('rs_tipe_rs') == 'A' ? 'selected' : '' }}>Tipe A</option>
                                <option value="B" {{ old('rs_tipe_rs') == 'B' ? 'selected' : '' }}>Tipe B</option>
                                <option value="C" {{ old('rs_tipe_rs') == 'C' ? 'selected' : '' }}>Tipe C</option>
                                <option value="D" {{ old('rs_tipe_rs') == 'D' ? 'selected' : '' }}>Tipe D</option>
                                <option value="D Pratama" {{ old('rs_tipe_rs') == 'D Pratama' ? 'selected' : '' }}>Tipe D Pratama</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Tingkat Kemampuan Pelayanan
                            </label>

                            <input type="text"
                                   name="rs_kemampuan_pelayanan"
                                   value="{{ old('rs_kemampuan_pelayanan') }}"
                                   placeholder="Contoh: Rujukan Regional, RSU Kelas B"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Masa Berlaku Izin Operasional
                            </label>

                            <input type="date"
                                   name="rs_masa_izin"
                                   value="{{ old('rs_masa_izin') }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">
                        </div>

                    </div>


                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Ambulans Transportasi
                            </label>

                            <input type="number"
                                   min="0"
                                   name="rs_ambulans_transport"
                                   value="{{ old('rs_ambulans_transport', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500"
                                   placeholder="Jumlah unit">
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Ambulans Gadar (ICU)
                            </label>

                            <input type="number"
                                   min="0"
                                   name="rs_ambulans_gadar"
                                   value="{{ old('rs_ambulans_gadar', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500"
                                   placeholder="Jumlah unit">
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Status Pelayanan PONEK
                            </label>

                            <select name="rs_ponek"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs
                                           focus:bg-white focus:outline-none
                                           focus:ring-2 focus:ring-blue-500/20
                                           focus:border-blue-500">

                                <option value="Ya PONEK"
                                        {{ old('rs_ponek') == 'Ya PONEK' ? 'selected' : '' }}>
                                    PONEK (Ya PONEK)
                                </option>

                                <option value="Tidak PONEK"
                                        {{ old('rs_ponek', 'Tidak PONEK') == 'Tidak PONEK' ? 'selected' : '' }}>
                                    NON PONEK (Tidak PONEK)
                                </option>

                            </select>
                        </div>

                    </div>

                </div>


                <!-- 3. CHILD: KLINIK PRATAMA -->
                <div x-show="jenisFaskes === 'klinik_pratama'"
                     x-transition
                     class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kemampuan Layanan
                            </label>

                            <select name="kp_kategori_layanan"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs">
                                <option value="rawat_jalan" {{ old('kp_kategori_layanan', 'rawat_jalan') == 'rawat_jalan' ? 'selected' : '' }}>Rawat Jalan</option>
                                <option value="rawat_inap" {{ old('kp_kategori_layanan') == 'rawat_inap' ? 'selected' : '' }}>Rawat Inap</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kepemilikan
                            </label>

                            <select name="kp_kepemilikan"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs">
                                <option value="Swasta" {{ old('kp_kepemilikan', 'Swasta') == 'Swasta' ? 'selected' : '' }}>Swasta</option>
                                <option value="Pemerintah" {{ old('kp_kepemilikan') == 'Pemerintah' ? 'selected' : '' }}>Pemerintah</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Penanggung Jawab (PJ) Medis
                            </label>

                            <input type="text"
                                   name="kp_pj"
                                   value="{{ old('kp_pj') }}"
                                   placeholder="Contoh: dr. Tri Haryanto"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kontak Penanggung Jawab
                            </label>

                            <input type="text"
                                   name="kp_kontak_pj"
                                   value="{{ old('kp_kontak_pj') }}"
                                   placeholder="No. HP / WhatsApp"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">
                        </div>

                    </div>


                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Masa Berlaku Izin
                            </label>

                            <input type="date"
                                   name="kp_masa_izin"
                                   value="{{ old('kp_masa_izin') }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2 text-xs
                                          focus:bg-white focus:outline-none
                                          focus:ring-2 focus:ring-blue-500/20
                                          focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Jumlah TT Rawat Inap
                            </label>

                            <input type="number"
                                   min="0"
                                   name="kp_bed_rawat_inap"
                                   value="{{ old('kp_bed_rawat_inap', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs">
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Jumlah SDM Medis
                            </label>

                            <input type="number"
                                   min="0"
                                   name="kp_jumlah_sdm"
                                   value="{{ old('kp_jumlah_sdm', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs">
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Melayani BPJS
                            </label>

                            <select name="kp_bpjs"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs">

                                <option value="Ya"
                                        {{ old('kp_bpjs', 'Ya') == 'Ya' ? 'selected' : '' }}>
                                    Ya
                                </option>

                                <option value="Tidak"
                                        {{ old('kp_bpjs') == 'Tidak' ? 'selected' : '' }}>
                                    Tidak
                                </option>

                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Ambulans Transport
                            </label>

                            <input type="number"
                                   min="0"
                                   name="kp_ambulans_transport"
                                   value="{{ old('kp_ambulans_transport', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs"
                                   placeholder="Jumlah unit">
                        </div>

                    </div>


                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Jenis Layanan Medis
                        </label>

                        <input type="text"
                               name="kp_jenis_layanan"
                               value="{{ old('kp_jenis_layanan') }}"
                               placeholder="Contoh: Poli Umum, Poli Gigi, Bersalin 24 Jam"
                               class="input-modern w-full bg-slate-50
                                      border border-slate-200 rounded-lg
                                      px-3 py-2.5 text-xs
                                      focus:bg-white focus:outline-none
                                      focus:ring-2 focus:ring-blue-500/20
                                      focus:border-blue-500">
                    </div>

                </div>


                <!-- 4. CHILD: KLINIK UTAMA -->
                <div x-show="jenisFaskes === 'klinik_utama'"
                     x-transition
                     class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kemampuan Layanan
                            </label>

                            <select name="ku_kategori_layanan"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs">
                                <option value="rawat_jalan" {{ old('ku_kategori_layanan', 'rawat_jalan') == 'rawat_jalan' ? 'selected' : '' }}>Rawat Jalan</option>
                                <option value="rawat_inap" {{ old('ku_kategori_layanan') == 'rawat_inap' ? 'selected' : '' }}>Rawat Inap</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kepemilikan Klinik
                            </label>

                            <select name="ku_kepemilikan"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs">
                                <option value="Swasta" {{ old('ku_kepemilikan', 'Swasta') == 'Swasta' ? 'selected' : '' }}>Swasta</option>
                                <option value="Pemerintah" {{ old('ku_kepemilikan') == 'Pemerintah' ? 'selected' : '' }}>Pemerintah</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Penanggung Jawab (PJ) Medis
                            </label>

                            <input type="text"
                                   name="ku_pj"
                                   value="{{ old('ku_pj') }}"
                                   placeholder="Contoh: dr. Sp.B / Spesialis"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kontak Penanggung Jawab
                            </label>

                            <input type="text"
                                   name="ku_kontak_pj"
                                   value="{{ old('ku_kontak_pj') }}"
                                   placeholder="No. HP / WhatsApp"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs">
                        </div>

                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Masa Berlaku Izin
                            </label>

                            <input type="date"
                                   name="ku_masa_izin"
                                   value="{{ old('ku_masa_izin') }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Jumlah TT Rawat Inap
                            </label>

                            <input type="number"
                                   min="0"
                                   name="ku_bed_rawat_inap"
                                   value="{{ old('ku_bed_rawat_inap', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Melayani BPJS
                            </label>

                            <select name="ku_bpjs"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs">
                                <option value="Ya" {{ old('ku_bpjs') == 'Ya' ? 'selected' : '' }}>Ya</option>
                                <option value="Tidak" {{ old('ku_bpjs', 'Tidak') == 'Tidak' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Ambulans Transport
                            </label>

                            <input type="number"
                                   min="0"
                                   name="ku_ambulans"
                                   value="{{ old('ku_ambulans', 0) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs"
                                   placeholder="Jumlah unit">
                        </div>

                    </div>


                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Kemampuan Layanan Spesialistik
                        </label>

                        <textarea name="ku_kemampuan_layanan"
                                  rows="2"
                                  placeholder="Contoh: Spesialis Bedah Katarak, Jantung & Pembuluh Darah..."
                                  class="input-modern w-full bg-slate-50
                                         border border-slate-200 rounded-lg
                                         px-3 py-2.5 text-xs resize-none
                                         focus:bg-white focus:outline-none
                                         focus:ring-2 focus:ring-blue-500/20
                                         focus:border-blue-500">{{ old('ku_kemampuan_layanan') }}</textarea>
                    </div>

                </div>


                <!-- 5. CHILD: LABORATORIUM -->
                <div x-show="jenisFaskes === 'laboratorium'"
                     x-transition
                     class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Kepemilikan Laboratorium
                            </label>

                            <select name="lab_kepemilikan"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs">
                                <option value="Swasta" {{ old('lab_kepemilikan', 'Swasta') == 'Swasta' ? 'selected' : '' }}>Swasta</option>
                                <option value="Pemerintah" {{ old('lab_kepemilikan') == 'Pemerintah' ? 'selected' : '' }}>Pemerintah</option>
                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Masa Berlaku Izin Operasional
                            </label>

                            <input type="date"
                                   name="lab_masa_izin"
                                   value="{{ old('lab_masa_izin') }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2 text-xs">
                        </div>

                    </div>


                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Jenis Layanan Pemeriksaan Lab
                        </label>

                        <textarea name="lab_jenis_layanan"
                                  rows="2"
                                  placeholder="Contoh: Hematologi, Kimia Klinik, Imunologi, PCR Biomolekuler..."
                                  class="input-modern w-full bg-slate-50
                                         border border-slate-200 rounded-lg
                                         px-3 py-2.5 text-xs resize-none">{{ old('lab_jenis_layanan') }}</textarea>
                    </div>

                </div>


                <!-- 6. CHILD: UPKDK -->
                <div x-show="jenisFaskes === 'upkdk'"
                     x-transition
                     class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Pustu (Puskesmas Pembantu)
                            </label>

                            <select name="upkdk_is_pustu"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs">

                                <option value="Ya"
                                        {{ old('upkdk_is_pustu') == 'Ya' ? 'selected' : '' }}>
                                    Ya
                                </option>

                                <option value="Tidak"
                                        {{ old('upkdk_is_pustu', 'Tidak') == 'Tidak' ? 'selected' : '' }}>
                                    Tidak
                                </option>

                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                PKD (Pos Kesehatan Desa)
                            </label>

                            <select name="upkdk_is_pkd"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs">

                                <option value="Ya"
                                        {{ old('upkdk_is_pkd') == 'Ya' ? 'selected' : '' }}>
                                    Ya
                                </option>

                                <option value="Tidak"
                                        {{ old('upkdk_is_pkd', 'Tidak') == 'Tidak' ? 'selected' : '' }}>
                                    Tidak
                                </option>

                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Jumlah Tenaga Kesehatan (SDM)
                            </label>

                            <input type="number"
                                   min="0"
                                   name="upkdk_jumlah_sdm"
                                   value="{{ old('upkdk_jumlah_sdm', 2) }}"
                                   class="input-modern w-full bg-slate-50
                                          border border-slate-200 rounded-lg
                                          px-3 py-2.5 text-xs">
                        </div>

                    </div>

                </div>

                <!-- 7. CHILD: GRIYA SEHAT -->
                <div x-show="jenisFaskes === 'griya_sehat'"
                     x-transition
                     class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Penanggung Jawab (PJ) Medis / Pimpinan
                            </label>
                            <input type="text"
                                   name="gs_pj"
                                   value="{{ old('gs_pj') }}"
                                   placeholder="Nama lengkap & gelar penanggung jawab"
                                   class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Nomor Kontak Penanggung Jawab
                            </label>
                            <input type="text"
                                   name="gs_kontak_pj"
                                   value="{{ old('gs_kontak_pj') }}"
                                   placeholder="No. HP / WhatsApp"
                                   class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Masa Berlaku Izin Operasional
                            </label>
                            <input type="date"
                                   name="gs_masa_izin"
                                   value="{{ old('gs_masa_izin') }}"
                                   class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Jumlah Tenaga Kesehatan / SDM
                            </label>
                            <input type="number"
                                   min="0"
                                   name="gs_jumlah_sdm"
                                   value="{{ old('gs_jumlah_sdm', 0) }}"
                                   class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-xs"
                                   placeholder="Jumlah SDM">
                        </div>
                    </div>

                <!-- 8. CHILD: TPMD -->
                <div x-show="jenisFaskes === 'tpmd'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Masa Berlaku SIP (Surat Izin Praktik)</label>
                            <input type="date" name="tpmd_masa_sip" value="{{ old('tpmd_masa_sip') }}"
                                   class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-xs">
                        </div>
                    </div>
                </div>

                </div>

                <!-- SUB-BAGIAN: KOLOM TAMBAHAN (CUSTOM FIELDS) -->
                <div class="pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            <h3 class="text-xs font-bold text-slate-800">Kolom Tambahan</h3>
                            <span class="text-[10px] text-slate-400">&bull; Dropdown spesifikasi dinamis</span>
                        </div>
                        <button type="button" @click="openCustomFieldModal()"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>Tambah Kolom</span>
                        </button>
                    </div>

                    <!-- Dynamic Fields Grid (10 Tipe Data) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" x-show="visibleCustomFields.length > 0">
                        <template x-for="field in visibleCustomFields" :key="field.id">
                            <div class="space-y-1">
                                <label class="block text-xs font-semibold text-slate-700">
                                    <span x-text="field.label"></span>
                                    <template x-if="field.is_required">
                                        <span class="text-rose-500 font-bold">*</span>
                                    </template>
                                </label>

                                <!-- 1. TEXT -->
                                <template x-if="field.type === 'text'">
                                    <input type="text"
                                           :name="'custom[' + field.id + ']'"
                                           :required="field.is_required"
                                           :maxlength="field.config && field.config.max_length ? field.config.max_length : 255"
                                           :placeholder="field.config && field.config.placeholder ? field.config.placeholder : ''"
                                           x-model="customValues[field.id]"
                                           class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                </template>

                                <!-- 2. TEXTAREA -->
                                <template x-if="field.type === 'textarea'">
                                    <textarea :name="'custom[' + field.id + ']'"
                                              :required="field.is_required"
                                              :rows="field.config && field.config.rows ? field.config.rows : 3"
                                              :maxlength="field.config && field.config.max_length ? field.config.max_length : 2000"
                                              :placeholder="field.config && field.config.placeholder ? field.config.placeholder : ''"
                                              x-model="customValues[field.id]"
                                              class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></textarea>
                                </template>

                                <!-- 3. NUMBER -->
                                <template x-if="field.type === 'number'">
                                    <div class="relative">
                                        <input type="number"
                                               :name="'custom[' + field.id + ']'"
                                               :required="field.is_required"
                                               :step="field.config && field.config.decimals ? Math.pow(10, -field.config.decimals) : '1'"
                                               :min="field.config && field.config.min !== null ? field.config.min : ''"
                                               :max="field.config && field.config.max !== null ? field.config.max : ''"
                                               x-model="customValues[field.id]"
                                               class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                               :class="field.config && field.config.unit ? 'pr-12' : ''">
                                        <template x-if="field.config && field.config.unit">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400 select-none" x-text="field.config.unit"></span>
                                        </template>
                                    </div>
                                </template>

                                <!-- 4. DATE -->
                                <template x-if="field.type === 'date'">
                                    <input type="date"
                                           :name="'custom[' + field.id + ']'"
                                           :required="field.is_required"
                                           x-model="customValues[field.id]"
                                           class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                </template>

                                <!-- 5. BOOLEAN -->
                                <template x-if="field.type === 'boolean'">
                                    <select :name="'custom[' + field.id + ']'"
                                            :required="field.is_required"
                                            x-model="customValues[field.id]"
                                            class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                        <option value="">-- Belum Diisi --</option>
                                        <option value="Ya" :selected="customValues[field.id] === 'Ya' || customValues[field.id] === true || customValues[field.id] === '1'">Ya</option>
                                        <option value="Tidak" :selected="customValues[field.id] === 'Tidak' || customValues[field.id] === false || customValues[field.id] === '0'">Tidak</option>
                                    </select>
                                </template>

                                <!-- 6. SELECT -->
                                <template x-if="field.type === 'select'">
                                    <select :name="'custom[' + field.id + ']'"
                                            :required="field.is_required"
                                            x-model="customValues[field.id]"
                                            class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                        <option value="">-- Pilih --</option>
                                        <template x-for="opt in field.options" :key="opt">
                                            <option :value="opt" x-text="opt" :selected="customValues[field.id] == opt"></option>
                                        </template>
                                        <template x-if="field.old_unlisted_value">
                                            <option :value="field.old_unlisted_value" x-text="field.old_unlisted_value + ' (nilai lama)'" selected></option>
                                        </template>
                                    </select>
                                </template>

                                <!-- 7. MULTISELECT -->
                                <template x-if="field.type === 'multiselect'">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap gap-1.5 p-2 bg-slate-50 border border-slate-200 rounded-lg min-h-[38px]">
                                            <template x-for="opt in field.options" :key="opt">
                                                <label class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs border cursor-pointer transition select-none"
                                                       :class="isMultiselectChecked(field.id, opt) ? 'bg-indigo-600 text-white border-indigo-600 font-semibold' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'">
                                                    <input type="checkbox"
                                                           :name="'custom[' + field.id + '][]'"
                                                           :value="opt"
                                                           :checked="isMultiselectChecked(field.id, opt)"
                                                           @change="toggleMultiselect(field.id, opt, field.config ? field.config.max_select : null)"
                                                           class="hidden">
                                                    <span x-text="opt"></span>
                                                </label>
                                            </template>
                                        </div>
                                        <template x-if="field.config && field.config.max_select">
                                            <p class="text-[10px] text-slate-400">Maksimal: <span class="font-semibold text-slate-600" x-text="field.config.max_select"></span> pilihan</p>
                                        </template>
                                    </div>
                                </template>

                                <!-- 8. URL -->
                                <template x-if="field.type === 'url'">
                                    <input type="url"
                                           :name="'custom[' + field.id + ']'"
                                           :required="field.is_required"
                                           :placeholder="field.config && field.config.placeholder ? field.config.placeholder : 'https://...'"
                                           x-model="customValues[field.id]"
                                           class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                </template>

                                <!-- 9. EMAIL -->
                                <template x-if="field.type === 'email'">
                                    <input type="email"
                                           :name="'custom[' + field.id + ']'"
                                           :required="field.is_required"
                                           :placeholder="field.config && field.config.placeholder ? field.config.placeholder : 'nama@domain.com'"
                                           x-model="customValues[field.id]"
                                           class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                </template>

                                <!-- 10. PHONE -->
                                <template x-if="field.type === 'phone'">
                                    <input type="tel"
                                           :name="'custom[' + field.id + ']'"
                                           :required="field.is_required"
                                           :placeholder="field.config && field.config.placeholder ? field.config.placeholder : '08xxxxxxxxxx'"
                                           x-model="customValues[field.id]"
                                           class="input-modern w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                </template>

                                <!-- Help text -->
                                <template x-if="field.config && field.config.help_text">
                                    <p class="text-[10.5px] text-slate-400 mt-0.5" x-text="field.config.help_text"></p>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Empty state if no fields for this jenis -->
                    <div x-show="visibleCustomFields.length === 0"
                         class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-400">
                        <span>Belum ada kolom tambahan untuk jenis faskes ini. Klik </span>
                        <button type="button" @click="openCustomFieldModal()" class="text-indigo-600 font-semibold hover:underline">
                            + Tambah Kolom
                        </button>
                        <span> untuk menambahkan dropdown baru.</span>
                    </div>
                </div>

            </div>


            <!-- BUTTON ACTIONS -->
            <div class="flex items-center justify-end gap-2.5 pt-1">

                <a href="{{ route('faskes.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-200
                          bg-white hover:bg-slate-50 text-slate-700
                          text-xs font-semibold transition
                          inline-flex items-center gap-2">

                    <i class="fa-solid fa-arrow-left text-[10px]"></i>

                    <span>Batal</span>

                </a>


                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-blue-600
                               hover:bg-blue-700 text-white text-xs
                               font-semibold shadow-md shadow-blue-500/20
                               transition inline-flex items-center gap-2">

                    <i class="fa-solid fa-floppy-disk text-xs"></i>

                    <span>
                        Simpan Fasilitas Kesehatan
                    </span>

                </button>

            </div>

        </form>

    </main>

    <!-- Modal Tambah Kolom Tambahan (Alpine) - Mendukung 10 Tipe Data -->
    <div x-show="customModalOpen" x-cloak
         class="fixed inset-0 z-[2000] overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-100 relative my-8"
             @click.away="if(!isSavingCustomField) customModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i class="fa-solid fa-plus text-xs"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Tambah Kolom Tambahan Baru</h3>
                        <p class="text-[11px] text-slate-400">Pilih salah satu dari 10 tipe data input fleksibel</p>
                    </div>
                </div>
                <button type="button" @click="customModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Error message inside modal -->
            <template x-if="customModalError">
                <div class="mt-3 p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs" x-text="customModalError"></div>
            </template>

            <form @submit.prevent="submitNewCustomField()" class="mt-4 space-y-3.5">
                <!-- Tipe Data Input -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Data Input <span class="text-rose-500">*</span></label>
                    <select x-model="newField.type" required
                            class="input-modern w-full text-xs px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 font-semibold text-indigo-700">
                        <option value="text">Teks Pendek (text)</option>
                        <option value="textarea">Teks Panjang / Catatan (textarea)</option>
                        <option value="number">Angka / Jumlah (number)</option>
                        <option value="date">Tanggal (date)</option>
                        <option value="boolean">Ya / Tidak (boolean)</option>
                        <option value="select">Dropdown Satu Pilihan (select)</option>
                        <option value="multiselect">Pilihan Ganda / Multi-Select (multiselect)</option>
                        <option value="url">Tautan Website (url)</option>
                        <option value="email">Alamat Email (email)</option>
                        <option value="phone">Nomor Telepon (phone)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Label Kolom <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="newField.label" required placeholder="Contoh: Kapasitas Ruang Isolasi, Akreditasi..."
                           class="input-modern w-full text-xs px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Lingkup Jenis Faskes</label>
                        <select x-model="newField.jenis_faskes"
                                class="input-modern w-full text-xs px-2.5 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none">
                            <option value="">Semua Jenis (Global)</option>
                            <option value="puskesmas">Puskesmas</option>
                            <option value="rumah_sakit">Rumah Sakit</option>
                            <option value="klinik_pratama">Klinik Pratama</option>
                            <option value="klinik_utama">Klinik Utama</option>
                            <option value="laboratorium">Laboratorium</option>
                            <option value="upkdk">UPKDK</option>
                            <option value="griya_sehat">Griya Sehat</option>
                            <option value="tpmd">TPMD</option>
                            <option value="tpmdg">TPMDG</option>
                            <option value="tpmb">TPMB</option>
                            <option value="tpmp">TPMP</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Tampil</label>
                        <input type="number" x-model.number="newField.sort_order" min="0"
                               class="input-modern w-full text-xs px-3 py-2 border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                </div>

                <!-- Konfigurasi Teks Pendek -->
                <div x-show="newField.type === 'text'" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] text-slate-600 mb-0.5">Maks. Karakter (1-255)</label>
                            <input type="number" x-model.number="newField.config.max_length" min="1" max="255"
                                   class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-600 mb-0.5">Placeholder</label>
                            <input type="text" x-model="newField.config.placeholder" placeholder="Petunjuk input..."
                                   class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-600 mb-0.5">Teks Bantuan (Help Text)</label>
                        <input type="text" x-model="newField.config.help_text" placeholder="Keterangan cara pengisian..."
                               class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                    </div>
                </div>

                <!-- Konfigurasi Teks Panjang -->
                <div x-show="newField.type === 'textarea'" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] text-slate-600 mb-0.5">Maks. Karakter</label>
                            <input type="number" x-model.number="newField.config.max_length" min="1" max="5000"
                                   class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-600 mb-0.5">Tinggi Baris (Rows: 2-10)</label>
                            <input type="number" x-model.number="newField.config.rows" min="2" max="10"
                                   class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-600 mb-0.5">Teks Bantuan</label>
                        <input type="text" x-model="newField.config.help_text" placeholder="Keterangan catatan..."
                               class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                    </div>
                </div>

                <!-- Konfigurasi Angka -->
                <div x-show="newField.type === 'number'" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[11px] text-slate-600 mb-0.5">Desimal (0-4)</label>
                            <input type="number" x-model.number="newField.config.decimals" min="0" max="4"
                                   class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-600 mb-0.5">Nilai Min</label>
                            <input type="number" step="any" x-model.number="newField.config.min" placeholder="Bebas"
                                   class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-600 mb-0.5">Nilai Max</label>
                            <input type="number" step="any" x-model.number="newField.config.max" placeholder="Bebas"
                                   class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-600 mb-0.5">Satuan Akhiran (Unit)</label>
                        <input type="text" x-model="newField.config.unit" placeholder="Misal: unit, m², orang, tempat tidur..."
                               class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                    </div>
                </div>

                <!-- Konfigurasi Opsi Select / Multiselect -->
                <div x-show="newField.type === 'select' || newField.type === 'multiselect'" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-700">Daftar Pilihan Opsi <span class="text-rose-500">*</span></label>
                        <button type="button" @click="addModalOption()"
                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">
                            + Tambah Opsi
                        </button>
                    </div>
                    <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                        <template x-for="(opt, idx) in newField.options" :key="idx">
                            <div class="flex items-center gap-1.5">
                                <input type="text" x-model="newField.options[idx]"
                                       :placeholder="'Opsi ' + (idx + 1)"
                                       class="input-modern flex-1 text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <button type="button" @click="removeModalOption(idx)"
                                        x-show="newField.options.length > 1"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 transition">
                                    <i class="fa-solid fa-trash text-[11px]"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div x-show="newField.type === 'multiselect'" class="pt-2 border-t border-slate-200">
                        <label class="block text-[11px] text-slate-600 mb-0.5">Batas Maks. Pilihan yang Dapat Dipilih (Opsional)</label>
                        <input type="number" x-model.number="newField.config.max_select" min="1" max="30" placeholder="Kosong = tidak dibatasi"
                               class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                    </div>
                </div>

                <!-- Konfigurasi Teks Bantuan Umum -->
                <div x-show="['url', 'email', 'phone', 'date', 'boolean'].includes(newField.type)" class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div x-show="['url', 'email', 'phone'].includes(newField.type)">
                        <label class="block text-[11px] text-slate-600 mb-0.5">Placeholder</label>
                        <input type="text" x-model="newField.config.placeholder" placeholder="Petunjuk format..."
                               class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-600 mb-0.5">Teks Bantuan (Help Text)</label>
                        <input type="text" x-model="newField.config.help_text" placeholder="Petunjuk pengisian..."
                               class="input-modern w-full text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg bg-white">
                    </div>
                </div>

                <!-- Checkbox Pengaturan -->
                <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" x-model="newField.is_required"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700 font-medium">Wajib diisi</span>
                    </label>

                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" x-model="newField.is_filterable"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700 font-medium">Bisa difilter</span>
                    </label>

                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" x-model="newField.show_in_list"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700 font-medium">Tampil di tabel</span>
                    </label>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="customModalOpen = false" :disabled="isSavingCustomField"
                            class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="isSavingCustomField"
                            class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition inline-flex items-center gap-1.5">
                        <template x-if="isSavingCustomField">
                            <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                        </template>
                        <span>Simpan Kolom</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Leaflet & Alpine.js Logic -->
    <script>
        function faskesFormApp(config) {
            return {
                jenisFaskes: config.initialJenis,
                lat: config.initialLat,
                lng: config.initialLng,
                map: null,
                marker: null,

                // Custom Fields (Tugas 2)
                customFieldDefinitions: @json($customFieldDefinitions ?? []),
                customValues: @json($existingCustomValues ?? (old('custom') ?: (object)[])),
                customModalOpen: false,
                isSavingCustomField: false,
                customModalError: null,
                newField: {
                    label: '',
                    jenis_faskes: '',
                    type: 'text',
                    options: ['Opsi 1', 'Opsi 2'],
                    config: {
                        max_length: 255,
                        rows: 3,
                        decimals: 0,
                        min: null,
                        max: null,
                        unit: '',
                        max_select: null,
                        placeholder: '',
                        help_text: ''
                    },
                    is_required: false,
                    is_filterable: true,
                    show_in_list: true,
                    sort_order: 0
                },

                get visibleCustomFields() {
                    const currentJenis = this.jenisFaskes;
                    return this.customFieldDefinitions.filter(f => {
                        if (!f.is_active) return false;
                        if (!f.jenis_faskes) return true;
                        return f.jenis_faskes === currentJenis;
                    }).map(f => {
                        const currentVal = this.customValues ? this.customValues[f.id] : null;
                        const opts = Array.isArray(f.options) ? f.options : [];
                        let oldUnlisted = null;
                        if (currentVal && !opts.includes(currentVal)) {
                            oldUnlisted = currentVal;
                        }
                        return {
                            ...f,
                            options: opts,
                            old_unlisted_value: oldUnlisted
                        };
                    });
                },

                isMultiselectChecked(fieldId, opt) {
                    const val = this.customValues ? this.customValues[fieldId] : null;
                    if (Array.isArray(val)) return val.includes(opt);
                    if (typeof val === 'string' && val) return val.split(';').map(s => s.trim()).includes(opt);
                    return false;
                },
                toggleMultiselect(fieldId, opt, maxSelect) {
                    if (!this.customValues) this.customValues = {};
                    let val = this.customValues[fieldId];
                    let arr = Array.isArray(val) ? [...val] : (typeof val === 'string' && val ? val.split(';').map(s => s.trim()) : []);
                    const idx = arr.indexOf(opt);
                    if (idx > -1) {
                        arr.splice(idx, 1);
                    } else {
                        if (maxSelect && arr.length >= maxSelect) {
                            alert('Maksimal ' + maxSelect + ' pilihan untuk kolom ini.');
                            return;
                        }
                        arr.push(opt);
                    }
                    this.customValues[fieldId] = arr;
                },

                openCustomFieldModal() {
                    this.newField = {
                        label: '',
                        jenis_faskes: this.jenisFaskes || '',
                        type: 'text',
                        options: ['Opsi 1', 'Opsi 2'],
                        config: {
                            max_length: 255,
                            rows: 3,
                            decimals: 0,
                            min: null,
                            max: null,
                            unit: '',
                            max_select: null,
                            placeholder: '',
                            help_text: ''
                        },
                        is_required: false,
                        is_filterable: true,
                        show_in_list: true,
                        sort_order: 0
                    };
                    this.customModalError = null;
                    this.customModalOpen = true;
                },

                addModalOption() {
                    if (this.newField.options.length < 30) {
                        this.newField.options.push('');
                    }
                },

                removeModalOption(idx) {
                    if (this.newField.options.length > 1) {
                        this.newField.options.splice(idx, 1);
                    }
                },

                async submitNewCustomField() {
                    if (!this.newField.label.trim()) {
                        this.customModalError = 'Label kolom wajib diisi.';
                        return;
                    }

                    if (['select', 'multiselect'].includes(this.newField.type)) {
                        const cleanOpts = this.newField.options.map(o => o.trim()).filter(o => o !== '');
                        if (cleanOpts.length === 0) {
                            this.customModalError = 'Minimal harus ada 1 opsi pilihan.';
                            return;
                        }
                    }

                    this.isSavingCustomField = true;
                    this.customModalError = null;

                    const payload = {
                        label: this.newField.label.trim(),
                        jenis_faskes: this.newField.jenis_faskes || null,
                        type: this.newField.type,
                        is_required: this.newField.is_required ? 1 : 0,
                        is_filterable: this.newField.is_filterable ? 1 : 0,
                        show_in_list: this.newField.show_in_list ? 1 : 0,
                        sort_order: this.newField.sort_order || 0,
                        config: this.newField.config
                    };

                    if (['select', 'multiselect'].includes(this.newField.type)) {
                        payload.options = this.newField.options.map(o => o.trim()).filter(o => o !== '');
                    }

                    try {
                        const response = await fetch('{{ url("/faskes-fields") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });

                        const resData = await response.json();

                        if (!response.ok || resData.status !== 'success') {
                            const errs = resData.errors ? Object.values(resData.errors).flat().join(' ') : (resData.message || 'Gagal menyimpan kolom.');
                            this.customModalError = errs;
                            this.isSavingCustomField = false;
                            return;
                        }

                        const createdField = resData.data;
                        createdField.is_active = true;
                        this.customFieldDefinitions.push(createdField);

                        this.customModalOpen = false;
                        this.isSavingCustomField = false;
                    } catch (e) {
                        this.customModalError = 'Terjadi kesalahan jaringan: ' + e.message;
                        this.isSavingCustomField = false;
                    }
                },

                addressQuery: '',
                addressResults: [],
                isSearchingAddress: false,
                hasSearchedAddress: false,
                addressSearchError: null,
                lastSourceIsGeocode: false,

                _addressDebounceTimer: null,
                _addressAbortController: null,

                initMiniMap() {
                    this.map = L.map('mini-map', {
                        zoomControl: false
                    }).setView([this.lat, this.lng], 14);

                    L.control.zoom({
                        position: 'bottomright'
                    }).addTo(this.map);

                    FaskesBasemaps.add(this.map, { position: 'topleft' });

                    this.marker = L.marker(
                        [this.lat, this.lng],
                        {
                            draggable: true
                        }
                    ).addTo(this.map);

                    this.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();

                        this.lat = parseFloat(pos.lat.toFixed(7));
                        this.lng = parseFloat(pos.lng.toFixed(7));

                        this.lastSourceIsGeocode = false;
                    });

                    this.map.on('click', (e) => {
                        this.lat = parseFloat(e.latlng.lat.toFixed(7));
                        this.lng = parseFloat(e.latlng.lng.toFixed(7));

                        this.marker.setLatLng([
                            this.lat,
                            this.lng
                        ]);

                        this.lastSourceIsGeocode = false;
                    });
                },


                onManualCoordinateChange() {

                    if (
                        !isNaN(this.lat) &&
                        !isNaN(this.lng) &&
                        this.lat >= -90 &&
                        this.lat <= 90 &&
                        this.lng >= -180 &&
                        this.lng <= 180
                    ) {

                        this.marker.setLatLng([
                            this.lat,
                            this.lng
                        ]);

                        this.map.panTo([
                            this.lat,
                            this.lng
                        ]);

                        this.lastSourceIsGeocode = false;
                    }
                },


                getCurrentLocation() {

                    if (navigator.geolocation) {

                        navigator.geolocation.getCurrentPosition(

                            (pos) => {

                                this.lat =
                                    parseFloat(
                                        pos.coords.latitude.toFixed(7)
                                    );

                                this.lng =
                                    parseFloat(
                                        pos.coords.longitude.toFixed(7)
                                    );

                                this.marker.setLatLng([
                                    this.lat,
                                    this.lng
                                ]);

                                this.map.setView([
                                    this.lat,
                                    this.lng
                                ], 15);

                                this.lastSourceIsGeocode = false;
                            },

                            (err) => alert(
                                'Gagal mendeteksi lokasi: ' +
                                err.message
                            )
                        );

                    } else {

                        alert(
                            'Browser Anda tidak mendukung Geolocation.'
                        );

                    }
                },


                onAddressInput() {

                    this.addressSearchError = null;

                    if (this._addressDebounceTimer) {
                        clearTimeout(
                            this._addressDebounceTimer
                        );
                    }

                    const query =
                        this.addressQuery.trim();

                    if (query.length < 3) {

                        this.clearAddressResults();

                        return;
                    }

                    this._addressDebounceTimer =
                        setTimeout(() => {

                            this.performAddressSearch(query);

                        }, 500);
                },


                performAddressSearch(query) {

                    if (this._addressAbortController) {

                        this._addressAbortController.abort();
                    }

                    this._addressAbortController =
                        new AbortController();

                    this.isSearchingAddress = true;
                    this.hasSearchedAddress = false;

                    fetch(
                        `{{ url('/api/geocode/search') }}?q=${encodeURIComponent(query)}`,
                        {
                            signal:
                                this._addressAbortController.signal
                        }
                    )
                    .then(res => res.json())

                    .then(res => {

                        this.isSearchingAddress = false;
                        this.hasSearchedAddress = true;

                        if (!res.success) {

                            this.addressResults = [];

                            this.addressSearchError =
                                res.message ||
                                'Gagal mencari alamat.';

                            return;
                        }

                        this.addressResults =
                            res.data || [];

                    })

                    .catch(err => {

                        if (
                            err.name === 'AbortError'
                        ) {
                            return;
                        }

                        this.isSearchingAddress = false;
                        this.hasSearchedAddress = true;
                        this.addressResults = [];

                        this.addressSearchError =
                            'Tidak dapat menghubungi layanan pencarian alamat. Silakan tandai lokasi manual di peta.';

                        console.error(
                            'Geocode search error:',
                            err
                        );
                    });
                },


                selectAddressResult(result) {

                    this.lat =
                        parseFloat(
                            Number(result.lat).toFixed(7)
                        );

                    this.lng =
                        parseFloat(
                            Number(result.lon).toFixed(7)
                        );

                    this.marker.setLatLng([
                        this.lat,
                        this.lng
                    ]);

                    this.map.setView([
                        this.lat,
                        this.lng
                    ], 16);

                    this.lastSourceIsGeocode = true;

                    if (
                        this.$refs.alamatInput &&
                        !this.$refs.alamatInput.value.trim()
                    ) {

                        this.$refs.alamatInput.value =
                            result.display_name;
                    }

                    this.addressQuery =
                        result.display_name;

                    this.clearAddressResults();
                },


                clearAddressResults() {

                    this.addressResults = [];
                    this.hasSearchedAddress = false;
                    this.addressSearchError = null;
                }
            };
        }
    </script>

</body>
</html>

