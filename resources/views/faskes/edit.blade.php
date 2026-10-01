<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Faskes: {{ $faskes->nama }} - Kab. Banyumas</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        .leaflet-control-zoom a {
            color: #334155 !important;
            font-weight: 600;
        }

        .leaflet-control-zoom {
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .12) !important;
        }

        .leaflet-control-zoom a:first-child {
            border-bottom: 1px solid #e2e8f0 !important;
        }

        input, select, textarea {
            box-shadow: 0 1px 2px rgba(15, 23, 42, .02);
        }

        select {
            cursor: pointer;
        }
    </style>

</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col font-sans"
      x-data="faskesFormApp({
          initialJenis: '{{ old('jenis_faskes', $faskes->jenis_faskes) }}',
          initialLat: {{ old('latitude', (float)$faskes->latitude) }},
          initialLng: {{ old('longitude', (float)$faskes->longitude) }}
      })"
      x-init="initMiniMap()">

    <!-- Header Navigation -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 px-4 sm:px-6 py-4 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('faskes.index') }}" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 transition flex items-center justify-center border border-slate-200">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h1 class="text-sm sm:text-base font-bold text-slate-900 leading-tight">Edit Data Faskes: {{ $faskes->nama }}</h1>
                    <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">ID Faskes: #{{ $faskes->id }} &bull; Perbarui Data & Posisi Spasial PostGIS</p>
                </div>
            </div>
            <a href="{{ route('faskes.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-blue-600 px-3 py-2 rounded-lg hover:bg-blue-50 transition">
                Batal
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-6 sm:py-8">

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-4 rounded-xl text-xs shadow-sm">
                <div class="font-bold mb-1">Terdapat kesalahan input:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('faskes.update', $faskes->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- SECTION 1: DATA UTAMA & KOORDINAT SPASIAL -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Left Column: Data Pokok Faskes (7 Cols) -->
                <div class="lg:col-span-7 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-clipboard-list"></i></span>
                        <span>Informasi Pokok Faskes</span>
                    </h2>

                    <!-- Nama Faskes -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Nama Fasilitas Kesehatan <span class="text-red-500">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama', $faskes->nama) }}" required
                               placeholder="Contoh: RSUD Banyumas, Puskesmas Sokaraja 1"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                    </div>

                    <!-- Jenis Faskes & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jenis Faskes <span class="text-red-500">*</span></label>
                            <select name="jenis_faskes" x-model="jenisFaskes" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
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
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Status Operasional <span class="text-red-500">*</span></label>
                            <select name="status" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="aktif" {{ old('status', $faskes->status) == 'aktif' ? 'selected' : '' }}>● Aktif Melayani</option>
                                <option value="nonaktif" {{ old('status', $faskes->status) == 'nonaktif' ? 'selected' : '' }}>○ Nonaktif / Tutup</option>
                            </select>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Alamat Lengkap</label>
                        <textarea name="alamat" rows="2" placeholder="Nama jalan, nomor gedung, RT/RW..."
                                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">{{ old('alamat', $faskes->alamat) }}</textarea>
                    </div>

                    <!-- Wilayah Administratif -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kecamatan</label>
                            <input type="text" name="kecamatan" value="{{ old('kecamatan', $faskes->kecamatan) }}"
                                   placeholder="Contoh: Purwokerto Timur, Sokaraja, Ajibarang"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Desa / Kelurahan</label>
                            <input type="text" name="desa" value="{{ old('desa', $faskes->desa) }}"
                                   placeholder="Contoh: Mersi, Sudagaran, Rempoah"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>

                    <!-- Nomor Telepon -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Nomor Telepon / Kontak IGD</label>
                        <input type="text" name="nomor_telepon" value="{{ old('nomor_telepon', $faskes->nomor_telepon) }}"
                               placeholder="Contoh: 0281-632708"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                </div>

                <!-- Right Column: Mini-Map & Koordinat (5 Cols) -->
                <div class="lg:col-span-5 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-location-dot"></i></span>
                            <span>Titik Koordinat Spasial</span>
                        </h2>
                        <span class="text-[10px] bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg font-semibold border border-blue-100">PostGIS SRID 4326</span>
                    </div>

                    <!-- Mini Map Container -->
                    <div class="relative flex-1 min-h-[300px] rounded-2xl overflow-hidden border border-slate-200 shadow-inner bg-slate-100">
                        <div id="mini-map" class="h-full w-full"></div>
                        <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-sm px-3 py-2 rounded-lg shadow-md border border-slate-200 text-[10px] text-slate-600 z-[500] flex items-center gap-2">
                            Klik peta atau geser marker untuk mengubah koordinat
                        </div>
                    </div>

                    <!-- Input Latitude & Longitude -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Latitude (Lintang) <span class="text-red-500">*</span></label>
                            <input type="number" step="any" name="latitude" x-model.number="lat" @input="onManualCoordinateChange()" required
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono text-slate-800 transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Longitude (Bujur) <span class="text-red-500">*</span></label>
                            <input type="number" step="any" name="longitude" x-model.number="lng" @input="onManualCoordinateChange()" required
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono text-slate-800 transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>

                    <button type="button" @click="getCurrentLocation()"
                            class="w-full py-2.5 px-3 bg-slate-100 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 hover:border-blue-200 text-slate-700 rounded-xl text-xs font-semibold flex items-center justify-center gap-2 transition">
                        <i class="fa-solid fa-location-crosshairs"></i>
                        <span>Deteksi Lokasi GPS Saya</span>
                    </button>
                </div>
            </div>

            <!-- SECTION 2: FIELD DINAMIS CHILD DETAIL SESUAI JENIS FASKES -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-sliders"></i></span>
                        <span>Spesifikasi & Detail Layanan</span>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-100 capitalize" x-text="jenisFaskes.replace('_', ' ')"></span>
                    </h2>
                    <span class="text-xs text-slate-400">Field child menyesuaikan jenis faskes</span>
                </div>

                <!-- 1. CHILD: PUSKESMAS -->
                @php
                    $pkm = $faskes->puskesmasDetail;
                    $initialPersalinan = 'NON PONED (Tidak Mampu Salin)';
                    if ($pkm?->poned === 'Ya PONED') {
                        $initialPersalinan = 'PONED';
                    } elseif ($pkm?->mampu_salin === 'Ya') {
                        $initialPersalinan = 'NON PONED (Mampu Salin)';
                    }
                    $valPersalinan = old('puskesmas_kemampuan_persalinan', $initialPersalinan);
                @endphp
                <div x-show="jenisFaskes === 'puskesmas'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kategori Pelayanan</label>
                            <select name="puskesmas_kategori" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="rawat_jalan" {{ old('puskesmas_kategori', $pkm?->kategori) == 'rawat_jalan' ? 'selected' : '' }}>Rawat Jalan</option>
                                <option value="rawat_inap" {{ old('puskesmas_kategori', $pkm?->kategori ?? 'rawat_inap') == 'rawat_inap' ? 'selected' : '' }}>Rawat Inap</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Klasifikasi Wilayah</label>
                            <select name="puskesmas_wilayah" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="perkotaan" {{ old('puskesmas_wilayah', $pkm?->wilayah ?? 'perkotaan') == 'perkotaan' ? 'selected' : '' }}>Perkotaan</option>
                                <option value="pedesaan" {{ old('puskesmas_wilayah', $pkm?->wilayah) == 'pedesaan' ? 'selected' : '' }}>Perdesaan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Masa Berlaku Izin Operasional</label>
                            <input type="date" name="puskesmas_masa_izin" value="{{ old('puskesmas_masa_izin', $pkm?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jumlah TT Rawat Inap</label>
                            <input type="number" min="0" name="puskesmas_jumlah_tempat_tidur" value="{{ old('puskesmas_jumlah_tempat_tidur', $pkm?->jumlah_tempat_tidur ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jumlah Tenaga Medis (SDM)</label>
                            <input type="number" min="0" name="puskesmas_jumlah_sdm" value="{{ old('puskesmas_jumlah_sdm', $pkm?->jumlah_sdm ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>

                    <!-- Layanan Persalinan & Ambulans -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kemampuan Pelayanan Persalinan</label>
                            <select name="puskesmas_kemampuan_persalinan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="PONED" {{ $valPersalinan === 'PONED' ? 'selected' : '' }}>PONED</option>
                                <option value="NON PONED (Mampu Salin)" {{ $valPersalinan === 'NON PONED (Mampu Salin)' ? 'selected' : '' }}>NON PONED (Mampu Salin)</option>
                                <option value="NON PONED (Tidak Mampu Salin)" {{ $valPersalinan === 'NON PONED (Tidak Mampu Salin)' ? 'selected' : '' }}>NON PONED (Tidak Mampu Salin)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Ambulans Transport (R4)</label>
                            <input type="number" min="0" name="puskesmas_ambulans_transport" value="{{ old('puskesmas_ambulans_transport', $pkm?->ambulans_transport ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10" placeholder="Jumlah unit">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Ambulans Motor (R2)</label>
                            <input type="number" min="0" name="puskesmas_ambulans_roda_dua" value="{{ old('puskesmas_ambulans_roda_dua', $pkm?->ambulans_roda_dua ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10" placeholder="Jumlah unit">
                        </div>
                    </div>
                </div>

                <!-- 2. CHILD: RUMAH SAKIT -->
                @php $rs = $faskes->rumahSakitDetail; @endphp
                <div x-show="jenisFaskes === 'rumah_sakit'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Tipe Rumah Sakit</label>
                            <select name="rs_tipe_rs" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="">-- Pilih Tipe RS --</option>
                                <option value="A" {{ old('rs_tipe_rs', $rs?->tipe_rs) == 'A' ? 'selected' : '' }}>Tipe A</option>
                                <option value="B" {{ old('rs_tipe_rs', $rs?->tipe_rs) == 'B' ? 'selected' : '' }}>Tipe B</option>
                                <option value="C" {{ old('rs_tipe_rs', $rs?->tipe_rs) == 'C' ? 'selected' : '' }}>Tipe C</option>
                                <option value="D" {{ old('rs_tipe_rs', $rs?->tipe_rs) == 'D' ? 'selected' : '' }}>Tipe D</option>
                                <option value="D Pratama" {{ old('rs_tipe_rs', $rs?->tipe_rs) == 'D Pratama' ? 'selected' : '' }}>Tipe D Pratama</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Tingkat Kemampuan Pelayanan</label>
                            <input type="text" name="rs_kemampuan_pelayanan" value="{{ old('rs_kemampuan_pelayanan', $rs?->kemampuan_pelayanan) }}"
                                   placeholder="Contoh: Rujukan Regional, RSU Kelas B"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Masa Berlaku Izin Operasional</label>
                            <input type="date" name="rs_masa_izin" value="{{ old('rs_masa_izin', $rs?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Ambulans Transportasi</label>
                            <input type="number" min="0" name="rs_ambulans_transport" value="{{ old('rs_ambulans_transport', $rs?->ambulans_transport ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10" placeholder="Jumlah unit">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Ambulans Gadar (ICU)</label>
                            <input type="number" min="0" name="rs_ambulans_gadar" value="{{ old('rs_ambulans_gadar', $rs?->ambulans_gadar ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10" placeholder="Jumlah unit">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Status Pelayanan PONEK</label>
                            <select name="rs_ponek" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="Ya PONEK" {{ old('rs_ponek', $rs?->ponek) == 'Ya PONEK' ? 'selected' : '' }}>PONEK (Ya PONEK)</option>
                                <option value="Tidak PONEK" {{ old('rs_ponek', $rs?->ponek ?? 'Tidak PONEK') == 'Tidak PONEK' ? 'selected' : '' }}>NON PONEK (Tidak PONEK)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. CHILD: KLINIK PRATAMA -->
                @php $kp = $faskes->klinikPratamaDetail; @endphp
                <div x-show="jenisFaskes === 'klinik_pratama'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kemampuan Layanan</label>
                            <select name="kp_kategori_layanan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="rawat_jalan" {{ old('kp_kategori_layanan', $kp?->kategori_layanan ?? 'rawat_jalan') == 'rawat_jalan' ? 'selected' : '' }}>Rawat Jalan</option>
                                <option value="rawat_inap" {{ old('kp_kategori_layanan', $kp?->kategori_layanan) == 'rawat_inap' ? 'selected' : '' }}>Rawat Inap</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kepemilikan</label>
                            <select name="kp_kepemilikan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="Swasta" {{ old('kp_kepemilikan', $kp?->kepemilikan ?? 'Swasta') == 'Swasta' ? 'selected' : '' }}>Swasta</option>
                                <option value="Pemerintah" {{ old('kp_kepemilikan', $kp?->kepemilikan) == 'Pemerintah' ? 'selected' : '' }}>Pemerintah</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Penanggung Jawab (PJ) Medis</label>
                            <input type="text" name="kp_pj" value="{{ old('kp_pj', $kp?->pj) }}" placeholder="Contoh: dr. Tri Haryanto"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kontak Penanggung Jawab</label>
                            <input type="text" name="kp_kontak_pj" value="{{ old('kp_kontak_pj', $kp?->kontak_pj) }}" placeholder="No. HP / WhatsApp"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Masa Berlaku Izin</label>
                            <input type="date" name="kp_masa_izin" value="{{ old('kp_masa_izin', $kp?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jumlah TT Rawat Inap</label>
                            <input type="number" min="0" name="kp_bed_rawat_inap" value="{{ old('kp_bed_rawat_inap', $kp?->bed_rawat_inap ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jumlah SDM Medis</label>
                            <input type="number" min="0" name="kp_jumlah_sdm" value="{{ old('kp_jumlah_sdm', $kp?->jumlah_sdm ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Melayani BPJS</label>
                            @php
                                $kpBpjsVal = old('kp_bpjs', $kp ? ($kp->bpjs ? 'Ya' : 'Tidak') : 'Ya');
                            @endphp
                            <select name="kp_bpjs" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="Ya" {{ $kpBpjsVal === 'Ya' ? 'selected' : '' }}>Ya</option>
                                <option value="Tidak" {{ $kpBpjsVal === 'Tidak' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Ambulans Transport</label>
                            <input type="number" min="0" name="kp_ambulans_transport" value="{{ old('kp_ambulans_transport', $kp?->ambulans_transport ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10" placeholder="Jumlah unit">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jenis Layanan Medis</label>
                        <input type="text" name="kp_jenis_layanan" value="{{ old('kp_jenis_layanan', $kp?->jenis_layanan) }}"
                               placeholder="Contoh: Poli Umum, Poli Gigi, Bersalin 24 Jam"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                </div>

                <!-- 4. CHILD: KLINIK UTAMA -->
                @php $ku = $faskes->klinikUtamaDetail; @endphp
                <div x-show="jenisFaskes === 'klinik_utama'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kemampuan Layanan</label>
                            <select name="ku_kategori_layanan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="rawat_jalan" {{ old('ku_kategori_layanan', $ku?->kategori_layanan ?? 'rawat_jalan') == 'rawat_jalan' ? 'selected' : '' }}>Rawat Jalan</option>
                                <option value="rawat_inap" {{ old('ku_kategori_layanan', $ku?->kategori_layanan) == 'rawat_inap' ? 'selected' : '' }}>Rawat Inap</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kepemilikan Klinik</label>
                            @php
                                $valKuKepemilikan = old('ku_kepemilikan', $ku?->kepemilikan ?? 'Swasta');
                                $isKuPemerintah = stripos((string)$valKuKepemilikan, 'pemerintah') !== false;
                            @endphp
                            <select name="ku_kepemilikan"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="Swasta" {{ !$isKuPemerintah ? 'selected' : '' }}>Swasta</option>
                                <option value="Pemerintah" {{ $isKuPemerintah ? 'selected' : '' }}>Pemerintah</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Penanggung Jawab (PJ) Medis</label>
                            <input type="text" name="ku_pj" value="{{ old('ku_pj', $ku?->pj) }}" placeholder="Contoh: dr. Sp.B / Spesialis"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kontak Penanggung Jawab</label>
                            <input type="text" name="ku_kontak_pj" value="{{ old('ku_kontak_pj', $ku?->kontak_pj) }}" placeholder="No. HP / WhatsApp"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Masa Berlaku Izin</label>
                            <input type="date" name="ku_masa_izin" value="{{ old('ku_masa_izin', $ku?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jumlah TT Rawat Inap</label>
                            <input type="number" min="0" name="ku_bed_rawat_inap" value="{{ old('ku_bed_rawat_inap', $ku?->bed_rawat_inap ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Melayani BPJS</label>
                            @php
                                $kuBpjsVal = old('ku_bpjs', $ku ? ($ku->bpjs ? 'Ya' : 'Tidak') : 'Tidak');
                            @endphp
                            <select name="ku_bpjs" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="Ya" {{ $kuBpjsVal === 'Ya' ? 'selected' : '' }}>Ya</option>
                                <option value="Tidak" {{ $kuBpjsVal === 'Tidak' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Ambulans Transport</label>
                            <input type="number" min="0" name="ku_ambulans" value="{{ old('ku_ambulans', $ku?->ambulans ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10" placeholder="Jumlah unit">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kemampuan Layanan Spesialistik</label>
                        <textarea name="ku_kemampuan_layanan" rows="2" placeholder="Contoh: Spesialis Bedah Katarak, Jantung & Pembuluh Darah..."
                                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">{{ old('ku_kemampuan_layanan', $ku?->kemampuan_layanan) }}</textarea>
                    </div>
                </div>

                <!-- 5. CHILD: LABORATORIUM -->
                @php $lab = $faskes->laboratoriumDetail; @endphp
                <div x-show="jenisFaskes === 'laboratorium'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Kepemilikan Laboratorium</label>
                            @php
                                $valLabKepemilikan = old('lab_kepemilikan', $lab?->kepemilikan ?? 'Swasta');
                                $isLabPemerintah = stripos((string)$valLabKepemilikan, 'pemerintah') !== false;
                            @endphp
                            <select name="lab_kepemilikan"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="Swasta" {{ !$isLabPemerintah ? 'selected' : '' }}>Swasta</option>
                                <option value="Pemerintah" {{ $isLabPemerintah ? 'selected' : '' }}>Pemerintah</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Masa Berlaku Izin Operasional</label>
                            <input type="date" name="lab_masa_izin" value="{{ old('lab_masa_izin', $lab?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jenis Layanan Pemeriksaan Lab</label>
                        <textarea name="lab_jenis_layanan" rows="2" placeholder="Contoh: Hematologi, Kimia Klinik, Imunologi, PCR Biomolekuler..."
                                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">{{ old('lab_jenis_layanan', $lab?->jenis_layanan) }}</textarea>
                    </div>
                </div>

                <!-- 6. CHILD: UPKDK -->
                @php $upk = $faskes->upkdkDetail; @endphp
                <div x-show="jenisFaskes === 'upkdk'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Pustu (Puskesmas Pembantu)</label>
                            <select name="upkdk_is_pustu" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="Ya" {{ old('upkdk_is_pustu', $upk?->is_pustu) == 'Ya' ? 'selected' : '' }}>Ya</option>
                                <option value="Tidak" {{ old('upkdk_is_pustu', $upk?->is_pustu ?? 'Tidak') == 'Tidak' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">PKD (Pos Kesehatan Desa)</label>
                            <select name="upkdk_is_pkd" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                <option value="Ya" {{ old('upkdk_is_pkd', $upk?->is_pkd) == 'Ya' ? 'selected' : '' }}>Ya</option>
                                <option value="Tidak" {{ old('upkdk_is_pkd', $upk?->is_pkd ?? 'Tidak') == 'Tidak' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jumlah Tenaga Kesehatan (SDM)</label>
                            <input type="number" min="0" name="upkdk_jumlah_sdm" value="{{ old('upkdk_jumlah_sdm', $upk?->jumlah_sdm ?? 2) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>
                </div>

                <!-- 7. CHILD: GRIYA SEHAT -->
                @php $gs = $faskes->griyaSehatDetail; @endphp
                <div x-show="jenisFaskes === 'griya_sehat'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Penanggung Jawab (PJ) Medis / Pimpinan</label>
                            <input type="text" name="gs_pj" value="{{ old('gs_pj', $gs?->pj) }}" placeholder="Nama lengkap & gelar penanggung jawab"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Nomor Kontak Penanggung Jawab</label>
                            <input type="text" name="gs_kontak_pj" value="{{ old('gs_kontak_pj', $gs?->kontak_pj) }}" placeholder="No. HP / WhatsApp"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Masa Berlaku Izin Operasional</label>
                            <input type="date" name="gs_masa_izin" value="{{ old('gs_masa_izin', $gs?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1.5">Jumlah Tenaga Kesehatan / SDM</label>
                            <input type="number" min="0" name="gs_jumlah_sdm" value="{{ old('gs_jumlah_sdm', $gs?->jumlah_sdm ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10" placeholder="Jumlah SDM">
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

                    <!-- Dynamic Fields Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" x-show="visibleCustomFields.length > 0">
                        <template x-for="field in visibleCustomFields" :key="field.id">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    <span x-text="field.label"></span>
                                    <template x-if="field.is_required">
                                        <span class="text-rose-500 font-bold">*</span>
                                    </template>
                                </label>
                                <select :name="'custom[' + field.id + ']'"
                                        :required="field.is_required"
                                        x-model="customValues[field.id]"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs transition focus:bg-white focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10">
                                    <option value="">-- Pilih --</option>
                                    <template x-for="opt in field.options" :key="opt">
                                        <option :value="opt" x-text="opt" :selected="customValues[field.id] == opt"></option>
                                    </template>
                                    <template x-if="field.old_unlisted_value">
                                        <option :value="field.old_unlisted_value" x-text="field.old_unlisted_value + ' (nilai lama)'" selected></option>
                                    </template>
                                </select>
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
            <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-2 border-t border-slate-200 mt-2 pt-5">
                <a href="{{ route('faskes.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition text-center">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-lg shadow-blue-500/20 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Perbarui Fasilitas Kesehatan</span>
                </button>
            </div>
        </form>

    </main>

    <!-- Modal Tambah Kolom Tambahan (Alpine) -->
    <div x-show="customModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 relative"
             @click.away="if(!isSavingCustomField) customModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i class="fa-solid fa-plus text-xs"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Tambah Kolom Tambahan</h3>
                        <p class="text-[11px] text-slate-400">Dropdown kustom baru langsung aktif di form</p>
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
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Label Kolom <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="newField.label" required placeholder="Contoh: Layanan 24 Jam, Akreditasi..."
                           class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Lingkup Jenis Faskes</label>
                        <select x-model="newField.jenis_faskes"
                                class="w-full text-xs px-2.5 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none">
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
                               class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg focus:outline-none">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold text-slate-700">Daftar Pilihan Opsi <span class="text-rose-500">*</span></label>
                        <button type="button" @click="addModalOption()"
                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">
                            + Tambah Opsi
                        </button>
                    </div>
                    <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                        <template x-for="(opt, idx) in newField.options" :key="idx">
                            <div class="flex items-center gap-1.5">
                                <input type="text" x-model="newField.options[idx]" required
                                       :placeholder="'Opsi ' + (idx + 1)"
                                       class="flex-1 text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <button type="button" @click="removeModalOption(idx)"
                                        x-show="newField.options.length > 1"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 transition">
                                    <i class="fa-solid fa-trash text-[11px]"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Maksimal 30 opsi per dropdown.</p>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="newField.is_required"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700 font-medium">Wajib diisi</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="customModalOpen = false" :disabled="isSavingCustomField"
                                class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                            Batal
                        </button>
                        <button type="submit" :disabled="isSavingCustomField"
                                class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition inline-flex items-center gap-1.5">
                            <template x-if="isSavingCustomField">
                                <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                            </template>
                            <span>Simpan</span>
                        </button>
                    </div>
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
                    options: ['Opsi 1', 'Opsi 2'],
                    is_required: false,
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

                openCustomFieldModal() {
                    this.newField = {
                        label: '',
                        jenis_faskes: this.jenisFaskes || '',
                        options: ['Opsi 1', 'Opsi 2'],
                        is_required: false,
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
                    const cleanOpts = this.newField.options.map(o => o.trim()).filter(o => o !== '');
                    if (cleanOpts.length === 0) {
                        this.customModalError = 'Minimal harus ada 1 opsi pilihan.';
                        return;
                    }

                    this.isSavingCustomField = true;
                    this.customModalError = null;

                    try {
                        const response = await fetch('{{ url("/faskes-fields") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                label: this.newField.label,
                                jenis_faskes: this.newField.jenis_faskes || null,
                                options: cleanOpts,
                                is_required: this.newField.is_required ? 1 : 0,
                                sort_order: this.newField.sort_order || 0
                            })
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

                initMiniMap() {
                    this.map = L.map('mini-map', {
                        zoomControl: false
                    }).setView([this.lat, this.lng], 14);

                    L.control.zoom({ position: 'bottomright' }).addTo(this.map);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(this.map);

                    // Marker kustom yang draggable
                    this.marker = L.marker([this.lat, this.lng], {
                        draggable: true
                    }).addTo(this.map);

                    // Saat marker digeser di peta
                    this.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.lat = parseFloat(pos.lat.toFixed(7));
                        this.lng = parseFloat(pos.lng.toFixed(7));
                    });

                    // Saat peta diklik
                    this.map.on('click', (e) => {
                        this.lat = parseFloat(e.latlng.lat.toFixed(7));
                        this.lng = parseFloat(e.latlng.lng.toFixed(7));
                        this.marker.setLatLng([this.lat, this.lng]);
                    });
                },

                // Saat input manual latitude/longitude diedit di form
                onManualCoordinateChange() {
                    if (!isNaN(this.lat) && !isNaN(this.lng) && this.lat >= -90 && this.lat <= 90 && this.lng >= -180 && this.lng <= 180) {
                        this.marker.setLatLng([this.lat, this.lng]);
                        this.map.panTo([this.lat, this.lng]);
                    }
                },

                // Ambil koordinat via Geolocation browser
                getCurrentLocation() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                this.lat = parseFloat(pos.coords.latitude.toFixed(7));
                                this.lng = parseFloat(pos.coords.longitude.toFixed(7));
                                this.marker.setLatLng([this.lat, this.lng]);
                                this.map.setView([this.lat, this.lng], 15);
                            },
                            (err) => alert('Gagal mendeteksi lokasi: ' + err.message)
                        );
                    } else {
                        alert('Browser Anda tidak mendukung Geolocation.');
                    }
                }
            };
        }
    </script>
</body>
</html>
