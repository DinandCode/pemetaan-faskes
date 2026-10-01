@php
    $icAmbulans = '<svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.83H14.25M16.5 18.75h-2.25m0-11.25h-8.25a1.125 1.125 0 00-1.125 1.125v8.25c0 .621.504 1.125 1.125 1.125h1.5m5.25-10.5V18.75m0-11.25H12" /></svg>';
@endphp

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase tracking-wide font-semibold text-[10.5px]">
                <tr>
                    <th class="py-3 px-3 text-center w-10">
                        <input type="checkbox"
                               @change="toggleSelectAll($event)"
                               :checked="isAllSelected"
                               :indeterminate="isIndeterminate"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                               title="Pilih semua di halaman ini">
                    </th>
                    <th class="py-3 px-4">Nama Faskes</th>
                    <th class="py-3 px-4">Jenis</th>
                    <th class="py-3 px-4">Wilayah / Alamat</th>
                    <th class="py-3 px-4">Koordinat (Lat, Lng)</th>
                    <th class="py-3 px-4">Spesifikasi Detail</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($faskesList as $faskes)
                    <tr class="hover:bg-slate-50/70 transition" :class="{ 'bg-blue-50/40': selectedIds.includes({{ $faskes->id }}) }">
                        <!-- Checkbox Kolom 3C -->
                        <td class="py-3 px-3 text-center align-top">
                            <input type="checkbox"
                                   :value="{{ $faskes->id }}"
                                   data-id="{{ $faskes->id }}"
                                   data-nama="{{ e($faskes->nama) }}"
                                   @change="toggleItem({{ $faskes->id }}, '{{ addslashes($faskes->nama) }}')"
                                   :checked="selectedIds.includes({{ $faskes->id }})"
                                   class="row-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </td>

                        <!-- Nama & Kontak -->
                        <td class="py-3 px-4 font-semibold text-slate-800 align-top">
                            <div class="font-bold text-slate-900 text-xs">{{ $faskes->nama }}</div>
                            @if($faskes->nomor_telepon)
                                <div class="text-[11px] text-slate-500 font-normal mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                    </svg>
                                    <span>{{ $faskes->nomor_telepon }}</span>
                                </div>
                            @endif
                        </td>

                        <!-- Jenis Badge -->
                        <td class="py-3 px-4 align-top">
                            @php
                                $badgeClasses = [
                                    'rumah_sakit'    => 'bg-red-50 text-red-700 border-red-200',
                                    'puskesmas'      => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'klinik_pratama' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'klinik_utama'   => 'bg-teal-50 text-teal-700 border-teal-200',
                                    'laboratorium'   => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'upkdk'          => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'griya_sehat'    => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                    'tpmd'           => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'tpmdg'          => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'tpmb'           => 'bg-pink-50 text-pink-700 border-pink-200',
                                    'tpmp'           => 'bg-lime-50 text-lime-700 border-lime-200',
                                ];
                                $labels = [
                                    'rumah_sakit'    => 'Rumah Sakit',
                                    'puskesmas'      => 'Puskesmas',
                                    'klinik_pratama' => 'Klinik Pratama',
                                    'klinik_utama'   => 'Klinik Utama',
                                    'laboratorium'   => 'Laboratorium',
                                    'upkdk'          => 'UPKDK',
                                    'griya_sehat'    => 'Griya Sehat',
                                    'tpmd'           => 'TPMD',
                                    'tpmdg'          => 'TPMDG',
                                    'tpmb'           => 'TPMB',
                                    'tpmp'           => 'TPMP',
                                ];
                                $badgeClass = $badgeClasses[$faskes->jenis_faskes] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                $label = $labels[$faskes->jenis_faskes] ?? $faskes->jenis_faskes;
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                {{ $label }}
                            </span>
                        </td>

                        <!-- Alamat & Kecamatan -->
                        <td class="py-3 px-4 text-slate-600 align-top">
                            <div>{{ $faskes->alamat ?: '-' }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                Kec. {{ $faskes->kecamatan ?: '-' }}, Desa {{ $faskes->desa ?: '-' }}
                            </div>
                        </td>

                        <!-- Koordinat -->
                        <td class="py-3 px-4 font-mono text-[11px] text-slate-600 align-top">
                            <div>{{ number_format($faskes->latitude, 6) }}</div>
                            <div>{{ number_format($faskes->longitude, 6) }}</div>
                        </td>

                        <!-- Spesifikasi Detail -->
                        <td class="py-3 px-4 text-[11px] align-top">
                            @if($faskes->jenis_faskes === 'puskesmas' && $faskes->puskesmasDetail)
                                @php $pkm = $faskes->puskesmasDetail; @endphp
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded font-medium text-[10px]">
                                            {{ $pkm->kategori === 'rawat_inap' ? 'Rawat Inap' : 'Non Rawat Inap' }}
                                        </span>
                                        @if($pkm->poned === 'Ya PONED')
                                            <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[10px] font-medium">PONED</span>
                                        @elseif($pkm->mampu_salin === 'Ya')
                                            <span class="px-1.5 py-0.5 bg-teal-100 text-teal-800 rounded text-[10px] font-medium">Mampu Salin</span>
                                        @endif
                                        @if($pkm->wilayah)
                                            <span class="text-slate-500 text-[10.5px]">&bull; {{ ucfirst(str_replace('_', ' ', $pkm->wilayah)) }}</span>
                                        @endif
                                    </div>
                                </div>

                            @elseif($faskes->jenis_faskes === 'rumah_sakit' && $faskes->rumahSakitDetail)
                                @php
                                    $rs = $faskes->rumahSakitDetail;
                                    $ambulansRs = (int) $rs->ambulans_transport + (int) $rs->ambulans_gadar;
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        @if($rs->tipe_rs)
                                            <span class="px-1.5 py-0.5 bg-red-100 text-red-800 font-bold rounded text-[10px]">Tipe {{ $rs->tipe_rs }}</span>
                                        @endif
                                        <span class="font-medium text-slate-700">{{ $rs->kemampuan_pelayanan ?: 'Umum' }}</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1">
                                        @if($rs->ponek === 'Ya PONEK')
                                            <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded text-[10px] font-medium">PONEK</span>
                                        @endif
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium inline-flex items-center gap-1 {{ $ambulansRs > 0 ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-400' }}">
                                            {!! $icAmbulans !!}
                                            {{ $ambulansRs }} Ambulans
                                        </span>
                                    </div>
                                </div>

                            @elseif($faskes->jenis_faskes === 'klinik_pratama' && $faskes->klinikPratamaDetail)
                                @php $kp = $faskes->klinikPratamaDetail; @endphp
                                <div class="space-y-1">
                                    <div class="flex flex-wrap gap-1">
                                        @if($kp->kategori_layanan)
                                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-600 font-medium">{{ ucfirst(str_replace('_', ' ', $kp->kategori_layanan)) }}</span>
                                        @endif
                                        @if($kp->kepemilikan)
                                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-600 font-medium">{{ $kp->kepemilikan }}</span>
                                        @endif
                                        @if($kp->bpjs)
                                            <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded font-medium">BPJS</span>
                                        @endif
                                        @if($kp->bed_rawat_inap > 0)
                                            <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium">{{ $kp->bed_rawat_inap }} Bed</span>
                                        @endif
                                        <span class="px-1.5 py-0.5 rounded font-medium inline-flex items-center gap-1 {{ $kp->ambulans_transport > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-400' }}">
                                            {!! $icAmbulans !!}
                                            {{ (int) $kp->ambulans_transport }} Ambulans
                                        </span>
                                    </div>
                                    <div class="text-slate-500 truncate max-w-[160px]">{{ $kp->jenis_layanan ?: '-' }}</div>
                                    <div class="text-slate-400 text-[10.5px]">SDM: {{ (int) $kp->jumlah_sdm }}</div>
                                </div>

                            @elseif($faskes->jenis_faskes === 'klinik_utama' && $faskes->klinikUtamaDetail)
                                @php $ku = $faskes->klinikUtamaDetail; @endphp
                                <div class="space-y-1">
                                    <div class="flex flex-wrap gap-1 items-center">
                                        @if($ku->kategori_layanan)
                                            <span class="px-1.5 py-0.5 bg-slate-100 rounded text-slate-600 font-medium">{{ ucfirst(str_replace('_', ' ', $ku->kategori_layanan)) }}</span>
                                        @endif
                                        <span class="font-medium text-slate-700">{{ $ku->kepemilikan ?: 'Swasta' }}</span>
                                        @if($ku->bpjs)
                                            <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded font-medium">BPJS</span>
                                        @endif
                                        @if($ku->bed_rawat_inap > 0)
                                            <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium">{{ $ku->bed_rawat_inap }} Bed</span>
                                        @endif
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium inline-flex items-center gap-1 {{ $ku->ambulans > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-400' }}">
                                            {!! $icAmbulans !!}
                                            {{ (int) $ku->ambulans }} Ambulans
                                        </span>
                                    </div>
                                    <div class="text-slate-500 truncate max-w-[160px]">{{ $ku->kemampuan_layanan ?: '-' }}</div>
                                </div>

                            @elseif($faskes->jenis_faskes === 'laboratorium' && $faskes->laboratoriumDetail)
                                @php $lab = $faskes->laboratoriumDetail; @endphp
                                <div class="space-y-0.5">
                                    <div class="text-slate-500 truncate max-w-[160px]">{{ $lab->jenis_layanan ?: 'Lab Umum' }}</div>
                                    @if($lab->kepemilikan)
                                        <div class="text-slate-400 text-[10.5px]">{{ $lab->kepemilikan }}</div>
                                    @endif
                                </div>

                            @elseif($faskes->jenis_faskes === 'upkdk' && $faskes->upkdkDetail)
                                @php $upk = $faskes->upkdkDetail; @endphp
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded font-semibold uppercase">{{ $upk->jenis ?? 'UPKDK' }}</span>
                                    @if($upk->is_pustu === 'Ya')
                                        <span class="px-1.5 py-0.5 bg-orange-100 text-orange-700 rounded font-medium">Pustu</span>
                                    @endif
                                    @if($upk->is_pkd === 'Ya')
                                        <span class="px-1.5 py-0.5 bg-teal-100 text-teal-700 rounded font-medium">PKD</span>
                                    @endif
                                    <span class="text-slate-500">SDM: {{ (int) $upk->jumlah_sdm }}</span>
                                </div>

                            @elseif($faskes->jenis_faskes === 'griya_sehat' && $faskes->griyaSehatDetail)
                                @php $gs = $faskes->griyaSehatDetail; @endphp
                                <div class="space-y-0.5">
                                    <div class="font-medium text-slate-700">Griya Sehat</div>
                                    <div class="text-slate-500 text-[10.5px]">
                                        @if($gs->pj) PJ: {{ $gs->pj }} @endif
                                        @if($gs->jumlah_sdm) &bull; SDM: {{ (int) $gs->jumlah_sdm }} @endif
                                    </div>
                                </div>

                            @elseif(in_array($faskes->jenis_faskes, ['tpmd', 'tpmdg', 'tpmb', 'tpmp']))
                                <div class="text-slate-500 italic text-[10.5px]">Praktik Mandiri</div>

                            @else
                                <span class="text-slate-400 italic">-</span>
                            @endif

                            {{-- Kolom Tambahan (Custom Fields) Chips --}}
                            @if($faskes->fieldValues && $faskes->fieldValues->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mt-1.5 pt-1 border-t border-slate-100">
                                    @foreach($faskes->fieldValues as $fv)
                                        @if($fv->definition && $fv->value)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200" title="{{ $fv->definition->label }}: {{ $fv->value }}">
                                                <span class="text-indigo-500 font-semibold">{{ $fv->definition->label }}:</span> {{ $fv->value }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        <!-- Status -->
                        <td class="py-3 px-4 align-top">
                            @if($faskes->status === 'aktif')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Nonaktif
                                </span>
                            @endif
                        </td>

                        <!-- Aksi -->
                        <td class="py-3 px-4 text-center align-top">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('faskes.edit', $faskes->id) }}"
                                   class="p-1.5 bg-slate-100 hover:bg-blue-50 text-slate-500 hover:text-blue-600 rounded-lg transition"
                                   title="Edit Faskes">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5V19.5a2.25 2.25 0 01-2.25 2.25H4.5A2.25 2.25 0 012.25 19.5V6.75A2.25 2.25 0 014.5 4.5h6" />
                                    </svg>
                                </a>
                                <form action="{{ route('faskes.destroy', $faskes->id) }}" method="POST"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus data faskes ini? Data detail juga akan dihapus.');"
                                      class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 rounded-lg transition"
                                            title="Hapus Faskes">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5M6 9.75V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v3.75" />
                            </svg>
                            <span class="text-xs">Tidak ada data faskes yang sesuai dengan filter pencarian.</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination Links -->
    @if($faskesList->hasPages())
        <div id="table-pagination" class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $faskesList->links() }}
        </div>
    @endif
</div>
