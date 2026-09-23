<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Faskes: {{ $faskes->nama }} - Kab. Banyumas</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans"
      x-data="faskesFormApp({
          initialJenis: '{{ old('jenis_faskes', $faskes->jenis_faskes) }}',
          initialLat: {{ old('latitude', (float)$faskes->latitude) }},
          initialLng: {{ old('longitude', (float)$faskes->longitude) }}
      })"
      x-init="initMiniMap()">

    <!-- Header Navigation -->
    <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-30 shadow-sm">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('faskes.index') }}" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition">
                    &larr;
                </a>
                <div>
                    <h1 class="text-base font-bold text-slate-800 leading-tight">Edit Data Faskes: {{ $faskes->nama }}</h1>
                    <p class="text-xs text-slate-500">ID Faskes: #{{ $faskes->id }} &bull; Perbarui Data & Posisi Spasial PostGIS</p>
                </div>
            </div>
            <a href="{{ route('faskes.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-800">
                Batal
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-6xl w-full mx-auto p-4 sm:p-6">

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs shadow-sm">
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
                <div class="lg:col-span-7 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2 flex items-center space-x-2">
                        <span>📋</span>
                        <span>Informasi Pokok Faskes</span>
                    </h2>

                    <!-- Nama Faskes -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Fasilitas Kesehatan <span class="text-red-500">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama', $faskes->nama) }}" required
                               placeholder="Contoh: RSUD Banyumas, Puskesmas Sokaraja 1"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Jenis Faskes & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Faskes <span class="text-red-500">*</span></label>
                            <select name="jenis_faskes" x-model="jenisFaskes" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="rumah_sakit">🏥 Rumah Sakit</option>
                                <option value="puskesmas">🩺 Puskesmas</option>
                                <option value="klinik_pratama">⚕️ Klinik Pratama</option>
                                <option value="klinik_utama">⚕️ Klinik Utama</option>
                                <option value="laboratorium">🔬 Laboratorium</option>
                                <option value="upkdk">🏛️ UPKDK (Pustu / PKD)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status Operasional <span class="text-red-500">*</span></label>
                            <select name="status" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="aktif" {{ old('status', $faskes->status) == 'aktif' ? 'selected' : '' }}>● Aktif Melayani</option>
                                <option value="nonaktif" {{ old('status', $faskes->status) == 'nonaktif' ? 'selected' : '' }}>○ Nonaktif / Tutup</option>
                            </select>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap</label>
                        <textarea name="alamat" rows="2" placeholder="Nama jalan, nomor gedung, RT/RW..."
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('alamat', $faskes->alamat) }}</textarea>
                    </div>

                    <!-- Wilayah Administratif -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan</label>
                            <input type="text" name="kecamatan" value="{{ old('kecamatan', $faskes->kecamatan) }}"
                                   placeholder="Contoh: Purwokerto Timur, Sokaraja, Ajibarang"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Desa / Kelurahan</label>
                            <input type="text" name="desa" value="{{ old('desa', $faskes->desa) }}"
                                   placeholder="Contoh: Mersi, Sudagaran, Rempoah"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Nomor Telepon -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Telepon / Kontak IGD</label>
                        <input type="text" name="nomor_telepon" value="{{ old('nomor_telepon', $faskes->nomor_telepon) }}"
                               placeholder="Contoh: 0281-632708"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Right Column: Mini-Map & Koordinat (5 Cols) -->
                <div class="lg:col-span-5 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h2 class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                            <span>📍</span>
                            <span>Titik Koordinat Spasial</span>
                        </h2>
                        <span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-semibold">PostGIS SRID 4326</span>
                    </div>

                    <!-- Mini Map Container -->
                    <div class="relative flex-1 min-h-[260px] rounded-xl overflow-hidden border border-slate-200 shadow-inner">
                        <div id="mini-map" class="h-full w-full"></div>
                        <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm px-2.5 py-1 rounded shadow text-[10px] text-slate-600 z-[500]">
                            Klik peta atau geser marker untuk mengubah koordinat
                        </div>
                    </div>

                    <!-- Input Latitude & Longitude -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-0.5">Latitude (Lintang) <span class="text-red-500">*</span></label>
                            <input type="number" step="any" name="latitude" x-model.number="lat" @input="onManualCoordinateChange()" required
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-mono text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-0.5">Longitude (Bujur) <span class="text-red-500">*</span></label>
                            <input type="number" step="any" name="longitude" x-model.number="lng" @input="onManualCoordinateChange()" required
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-mono text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <button type="button" @click="getCurrentLocation()"
                            class="w-full py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium flex items-center justify-center space-x-1.5 transition">
                        <span>🎯</span>
                        <span>Deteksi Lokasi GPS Saya</span>
                    </button>
                </div>
            </div>

            <!-- SECTION 2: FIELD DINAMIS CHILD DETAIL SESUAI JENIS FASKES -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="text-sm font-bold text-slate-800 flex items-center space-x-2">
                        <span>⚙️</span>
                        <span>Spesifikasi & Detail Layanan</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-100 text-blue-800 capitalize" x-text="jenisFaskes.replace('_', ' ')"></span>
                    </h2>
                    <span class="text-xs text-slate-400">Field child menyesuaikan jenis faskes</span>
                </div>

                <!-- 1. CHILD: PUSKESMAS -->
                @php $pkm = $faskes->puskesmasDetail; @endphp
                <div x-show="jenisFaskes === 'puskesmas'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Pelayanan</label>
                            <select name="puskesmas_kategori" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                                <option value="rawat_jalan" {{ old('puskesmas_kategori', $pkm?->kategori) == 'rawat_jalan' ? 'selected' : '' }}>Rawat Jalan</option>
                                <option value="rawat_inap" {{ old('puskesmas_kategori', $pkm?->kategori ?? 'rawat_inap') == 'rawat_inap' ? 'selected' : '' }}>Rawat Inap</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Klasifikasi Wilayah</label>
                            <select name="puskesmas_wilayah" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                                <option value="perkotaan" {{ old('puskesmas_wilayah', $pkm?->wilayah ?? 'perkotaan') == 'perkotaan' ? 'selected' : '' }}>Perkotaan</option>
                                <option value="pedesaan" {{ old('puskesmas_wilayah', $pkm?->wilayah) == 'pedesaan' ? 'selected' : '' }}>Pedesaan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Masa Berlaku Izin Operasional</label>
                            <input type="date" name="puskesmas_masa_izin" value="{{ old('puskesmas_masa_izin', $pkm?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Tempat Tidur (Bed)</label>
                            <input type="number" min="0" name="puskesmas_jumlah_tempat_tidur" value="{{ old('puskesmas_jumlah_tempat_tidur', $pkm?->jumlah_tempat_tidur ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Tenaga Medis (SDM)</label>
                            <input type="number" min="0" name="puskesmas_jumlah_sdm" value="{{ old('puskesmas_jumlah_sdm', $pkm?->jumlah_sdm ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                    </div>

                    <!-- Dropdowns Layanan & Input Ambulans -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status PONED</label>
                            <select name="puskesmas_poned" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                                <option value="Ya PONED" {{ old('puskesmas_poned', $pkm?->poned) == 'Ya PONED' ? 'selected' : '' }}>Ya PONED</option>
                                <option value="Tidak PONED" {{ old('puskesmas_poned', $pkm?->poned ?? 'Tidak PONED') == 'Tidak PONED' ? 'selected' : '' }}>Tidak PONED</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Mampu Salin</label>
                            <select name="puskesmas_mampu_salin" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                                <option value="Ya" {{ old('puskesmas_mampu_salin', $pkm?->mampu_salin ?? 'Ya') == 'Ya' ? 'selected' : '' }}>Ya</option>
                                <option value="Tidak" {{ old('puskesmas_mampu_salin', $pkm?->mampu_salin) == 'Tidak' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">🚑 Ambulans Transport (R4)</label>
                            <input type="number" min="0" name="puskesmas_ambulans_transport" value="{{ old('puskesmas_ambulans_transport', $pkm?->ambulans_transport ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs" placeholder="Jumlah unit">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">🛵 Ambulans Motor (R2)</label>
                            <input type="number" min="0" name="puskesmas_ambulans_roda_dua" value="{{ old('puskesmas_ambulans_roda_dua', $pkm?->ambulans_roda_dua ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs" placeholder="Jumlah unit">
                        </div>
                    </div>
                </div>

                <!-- 2. CHILD: RUMAH SAKIT -->
                @php $rs = $faskes->rumahSakitDetail; @endphp
                <div x-show="jenisFaskes === 'rumah_sakit'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Kemampuan Pelayanan</label>
                            <input type="text" name="rs_kemampuan_pelayanan" value="{{ old('rs_kemampuan_pelayanan', $rs?->kemampuan_pelayanan) }}"
                                   placeholder="Contoh: Rujukan Regional Tipe A, RSU Tipe B, RS Bedah Tipe C"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Masa Berlaku Izin Operasional</label>
                            <input type="date" name="rs_masa_izin" value="{{ old('rs_masa_izin', $rs?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">🚑 Ambulans Transportasi</label>
                            <input type="number" min="0" name="rs_ambulans_transport" value="{{ old('rs_ambulans_transport', $rs?->ambulans_transport ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs" placeholder="Jumlah unit">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">🚨 Ambulans Gadar (ICU)</label>
                            <input type="number" min="0" name="rs_ambulans_gadar" value="{{ old('rs_ambulans_gadar', $rs?->ambulans_gadar ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs" placeholder="Jumlah unit">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status PONEK</label>
                            <select name="rs_ponek" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                                <option value="Ya PONEK" {{ old('rs_ponek', $rs?->ponek) == 'Ya PONEK' ? 'selected' : '' }}>Ya PONEK</option>
                                <option value="Tidak PONEK" {{ old('rs_ponek', $rs?->ponek ?? 'Tidak PONEK') == 'Tidak PONEK' ? 'selected' : '' }}>Tidak PONEK</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. CHILD: KLINIK PRATAMA -->
                @php $kp = $faskes->klinikPratamaDetail; @endphp
                <div x-show="jenisFaskes === 'klinik_pratama'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Penanggung Jawab (PJ Medis)</label>
                            <input type="text" name="kp_pj" value="{{ old('kp_pj', $kp?->pj) }}" placeholder="Contoh: dr. Tri Haryanto"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kontak Penanggung Jawab</label>
                            <input type="text" name="kp_kontak_pj" value="{{ old('kp_kontak_pj', $kp?->kontak_pj) }}" placeholder="No. HP / WhatsApp"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Masa Izin Operasional</label>
                            <input type="date" name="kp_masa_izin" value="{{ old('kp_masa_izin', $kp?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Bed Rawat Inap</label>
                            <input type="number" min="0" name="kp_bed_rawat_inap" value="{{ old('kp_bed_rawat_inap', $kp?->bed_rawat_inap ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah SDM Medis</label>
                            <input type="number" min="0" name="kp_jumlah_sdm" value="{{ old('kp_jumlah_sdm', $kp?->jumlah_sdm ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Melayani BPJS</label>
                            @php
                                $kpBpjsVal = old('kp_bpjs', $kp ? ($kp->bpjs ? 'Ya' : 'Tidak') : 'Ya');
                            @endphp
                            <select name="kp_bpjs" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                                <option value="Ya" {{ $kpBpjsVal === 'Ya' ? 'selected' : '' }}>Ya</option>
                                <option value="Tidak" {{ $kpBpjsVal === 'Tidak' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">🚑 Ambulans Transport</label>
                            <input type="number" min="0" name="kp_ambulans_transport" value="{{ old('kp_ambulans_transport', $kp?->ambulans_transport ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs" placeholder="Jumlah unit">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Layanan Medis</label>
                        <input type="text" name="kp_jenis_layanan" value="{{ old('kp_jenis_layanan', $kp?->jenis_layanan) }}"
                               placeholder="Contoh: Poli Umum, Poli Gigi, Bersalin 24 Jam"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                    </div>
                </div>

                <!-- 4. CHILD: KLINIK UTAMA -->
                @php $ku = $faskes->klinikUtamaDetail; @endphp
                <div x-show="jenisFaskes === 'klinik_utama'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kepemilikan Klinik</label>
                            <input type="text" name="ku_kepemilikan" value="{{ old('ku_kepemilikan', $ku?->kepemilikan ?? 'Swasta') }}"
                                   placeholder="Contoh: Swasta, Yayasan, BUMN"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Masa Izin Operasional</label>
                            <input type="date" name="ku_masa_izin" value="{{ old('ku_masa_izin', $ku?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">🚑 Ambulans Khusus</label>
                            <input type="number" min="0" name="ku_ambulans" value="{{ old('ku_ambulans', $ku?->ambulans ?? 0) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs" placeholder="Jumlah unit">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kemampuan Layanan Spesialistik</label>
                        <textarea name="ku_kemampuan_layanan" rows="2" placeholder="Contoh: Spesialis Bedah Katarak, Jantung & Pembuluh Darah..."
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">{{ old('ku_kemampuan_layanan', $ku?->kemampuan_layanan) }}</textarea>
                    </div>
                </div>

                <!-- 5. CHILD: LABORATORIUM -->
                @php $lab = $faskes->laboratoriumDetail; @endphp
                <div x-show="jenisFaskes === 'laboratorium'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kepemilikan Laboratorium</label>
                            <input type="text" name="lab_kepemilikan" value="{{ old('lab_kepemilikan', $lab?->kepemilikan ?? 'Swasta') }}"
                                   placeholder="Contoh: PT Prodia Widyahusada Tbk, Pemerintah Daerah"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Masa Izin Operasional</label>
                            <input type="date" name="lab_masa_izin" value="{{ old('lab_masa_izin', $lab?->masa_izin?->format('Y-m-d')) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Layanan Pemeriksaan Lab</label>
                        <textarea name="lab_jenis_layanan" rows="2" placeholder="Contoh: Hematologi, Kimia Klinik, Imunologi, PCR Biomolekuler..."
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">{{ old('lab_jenis_layanan', $lab?->jenis_layanan) }}</textarea>
                    </div>
                </div>

                <!-- 6. CHILD: UPKDK -->
                @php $upk = $faskes->upkdkDetail; @endphp
                <div x-show="jenisFaskes === 'upkdk'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pustu (Puskesmas Pembantu)</label>
                            <select name="upkdk_is_pustu" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                                <option value="Ya" {{ old('upkdk_is_pustu', $upk?->is_pustu) == 'Ya' ? 'selected' : '' }}>Ya</option>
                                <option value="Tidak" {{ old('upkdk_is_pustu', $upk?->is_pustu ?? 'Tidak') == 'Tidak' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">PKD (Pos Kesehatan Desa)</label>
                            <select name="upkdk_is_pkd" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                                <option value="Ya" {{ old('upkdk_is_pkd', $upk?->is_pkd) == 'Ya' ? 'selected' : '' }}>Ya</option>
                                <option value="Tidak" {{ old('upkdk_is_pkd', $upk?->is_pkd ?? 'Tidak') == 'Tidak' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Tenaga Kesehatan (SDM)</label>
                            <input type="number" min="0" name="upkdk_jumlah_sdm" value="{{ old('upkdk_jumlah_sdm', $upk?->jumlah_sdm ?? 2) }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs">
                        </div>
                    </div>
                </div>
            </div>

            <!-- BUTTON ACTIONS -->
            <div class="flex items-center justify-end space-x-3 pt-2">
                <a href="{{ route('faskes.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md shadow-blue-500/20 transition flex items-center space-x-2">
                    <span>💾</span>
                    <span>Perbarui Fasilitas Kesehatan</span>
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
