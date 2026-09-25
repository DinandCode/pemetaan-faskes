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

    <style>
        [x-cloak] {
            display: none !important;
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
                                border border-slate-200 shadow-inner">

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
                                    Pedesaan
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
                                Jumlah Tempat Tidur (Bed)
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


                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-2">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Status PONED
                            </label>

                            <select name="puskesmas_poned"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs
                                           focus:bg-white focus:outline-none
                                           focus:ring-2 focus:ring-blue-500/20
                                           focus:border-blue-500">

                                <option value="Ya PONED"
                                        {{ old('puskesmas_poned') == 'Ya PONED' ? 'selected' : '' }}>
                                    Ya PONED
                                </option>

                                <option value="Tidak PONED"
                                        {{ old('puskesmas_poned', 'Tidak PONED') == 'Tidak PONED' ? 'selected' : '' }}>
                                    Tidak PONED
                                </option>

                            </select>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Mampu Salin
                            </label>

                            <select name="puskesmas_mampu_salin"
                                    class="input-modern w-full bg-slate-50
                                           border border-slate-200 rounded-lg
                                           px-3 py-2.5 text-xs
                                           focus:bg-white focus:outline-none
                                           focus:ring-2 focus:ring-blue-500/20
                                           focus:border-blue-500">

                                <option value="Ya"
                                        {{ old('puskesmas_mampu_salin', 'Ya') == 'Ya' ? 'selected' : '' }}>
                                    Ya
                                </option>

                                <option value="Tidak"
                                        {{ old('puskesmas_mampu_salin') == 'Tidak' ? 'selected' : '' }}>
                                    Tidak
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

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Tingkat Kemampuan Pelayanan
                            </label>

                            <input type="text"
                                   name="rs_kemampuan_pelayanan"
                                   value="{{ old('rs_kemampuan_pelayanan') }}"
                                   placeholder="Contoh: Rujukan Regional Tipe A, RSU Tipe B, RS Bedah Tipe C"
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
                                Status PONEK
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
                                    Ya PONEK
                                </option>

                                <option value="Tidak PONEK"
                                        {{ old('rs_ponek', 'Tidak PONEK') == 'Tidak PONEK' ? 'selected' : '' }}>
                                    Tidak PONEK
                                </option>

                            </select>
                        </div>

                    </div>

                </div>


                <!-- 3. CHILD: KLINIK PRATAMA -->
                <div x-show="jenisFaskes === 'klinik_pratama'"
                     x-transition
                     class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Penanggung Jawab (PJ Medis)
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


                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Masa Izin Operasional
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

                    </div>


                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Bed Rawat Inap
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

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

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
                                Masa Izin Operasional
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
                                Ambulans Transpot
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
                                Masa Izin Operasional
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


    <!-- Leaflet & Alpine.js Logic -->
    <script>
        function faskesFormApp(config) {
            return {
                jenisFaskes: config.initialJenis,
                lat: config.initialLat,
                lng: config.initialLng,
                map: null,
                marker: null,

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

                    L.tileLayer(
                        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                        {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap'
                        }
                    ).addTo(this.map);

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

