<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kolom Tambahan - SIG Faskes Banyumas</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans"
      x-data="{
          createModalOpen: false,
          editModalOpen: false,
          editField: {
              id: null,
              label: '',
              jenis_faskes: '',
              options: [''],
              is_required: false,
              is_active: true,
              sort_order: 0
          },
          createField: {
              label: '',
              jenis_faskes: '',
              options: ['Opsi 1', 'Opsi 2'],
              is_required: false,
              sort_order: 0
          },
          openEdit(field) {
              this.editField = {
                  id: field.id,
                  label: field.label,
                  jenis_faskes: field.jenis_faskes || '',
                  options: Array.isArray(field.options) ? [...field.options] : [],
                  is_required: !!field.is_required,
                  is_active: !!field.is_active,
                  sort_order: field.sort_order || 0
              };
              if (this.editField.options.length === 0) {
                  this.editField.options = [''];
              }
              this.editModalOpen = true;
          },
          addCreateOption() {
              if (this.createField.options.length < 30) {
                  this.createField.options.push('');
              }
          },
          removeCreateOption(index) {
              if (this.createField.options.length > 1) {
                  this.createField.options.splice(index, 1);
              }
          },
          addEditOption() {
              if (this.editField.options.length < 30) {
                  this.editField.options.push('');
              }
          },
          removeEditOption(index) {
              if (this.editField.options.length > 1) {
                  this.editField.options.splice(index, 1);
              }
          }
      }">

    <!-- Header Navigation -->
    <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm shadow-indigo-500/30 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-900 leading-tight tracking-tight">Kelola Kolom Tambahan (Custom Fields)</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Dinas Kesehatan Kabupaten Banyumas &bull; Input kustom dinamis untuk detail layanan</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('faskes.index') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    <span>Master Data Faskes</span>
                </a>
                <a href="{{ url('/peta') }}"
                   class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75L3 9.253v11.25L9 18m0-11.25l6 2.25m-6-2.25v11.25m6-9l6-2.25v11.25L15 18m0-11.25v11.25m0 0l-6 2.25" />
                    </svg>
                    <span>Peta GIS</span>
                </a>

                <button type="button" @click="createModalOpen = true"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm shadow-indigo-500/25 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>+ Tambah Kolom Tambahan</span>
                </button>
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
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M4.062 19.5h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4.5c-.77-1.333-2.694-1.333-3.464 0L2.33 16.5c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Terjadi kesalahan validasi:</span>
                </div>
                <ul class="list-disc list-inside text-red-700 pl-1 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
            <form method="GET" action="{{ route('faskes-fields.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-600">Jenis Faskes:</label>
                    <select name="jenis_faskes" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">-- Semua Lingkup --</option>
                        <option value="all" {{ request('jenis_faskes') === 'all' ? 'selected' : '' }}>Semua Jenis (Global)</option>
                        @foreach($validJenisFaskes as $jf)
                            <option value="{{ $jf }}" {{ request('jenis_faskes') === $jf ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $jf)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-600">Status:</label>
                    <select name="status" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">-- Semua Status --</option>
                        <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                @if(request('jenis_faskes') || request('status'))
                    <a href="{{ route('faskes-fields.index') }}" class="text-xs text-rose-600 hover:text-rose-700 font-medium">Reset Filter</a>
                @endif
            </form>

            <div class="text-xs text-slate-500">
                Total Definisi: <span class="font-bold text-slate-700">{{ $fields->total() }}</span> kolom
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">Label & Field Key</th>
                            <th class="py-3 px-4">Lingkup Faskes</th>
                            <th class="py-3 px-4">Daftar Opsi Dropdown</th>
                            <th class="py-3 px-4 text-center">Wajib</th>
                            <th class="py-3 px-4 text-center">Urutan</th>
                            <th class="py-3 px-4 text-center">Data Terisi</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($fields as $index => $field)
                            <tr class="hover:bg-slate-50/80 transition {{ !$field->is_active ? 'opacity-60 bg-slate-50/40' : '' }}">
                                <td class="py-3 px-4 text-center font-medium text-slate-400">
                                    {{ $fields->firstItem() + $index }}
                                </td>
                                <td class="py-3 px-4 align-top">
                                    <div class="font-bold text-slate-900 text-xs">{{ $field->label }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">custom[{{ $field->id }}] &bull; {{ $field->field_key }}</div>
                                </td>
                                <td class="py-3 px-4 align-top">
                                    @if(empty($field->jenis_faskes))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Semua Jenis (Global)
                                        </span>
                                    @else
                                        @php
                                            $badgeClass = match($field->jenis_faskes) {
                                                'rumah_sakit' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                'puskesmas' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'klinik_pratama', 'klinik_utama' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'laboratorium' => 'bg-purple-50 text-purple-700 border-purple-200',
                                                'upkdk' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'griya_sehat' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                                default => 'bg-sky-50 text-sky-700 border-sky-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                            {{ ucwords(str_replace('_', ' ', $field->jenis_faskes)) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 align-top">
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @foreach((array) $field->options as $opt)
                                            <span class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 rounded text-[10px]">
                                                {{ $opt }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center align-top">
                                    @if($field->is_required)
                                        <span class="text-rose-600 font-bold text-[11px]">Ya</span>
                                    @else
                                        <span class="text-slate-400 text-[11px]">Tidak</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center align-top font-mono text-slate-500">
                                    {{ $field->sort_order }}
                                </td>
                                <td class="py-3 px-4 text-center align-top font-semibold text-slate-600">
                                    {{ $field->values_count }} faskes
                                </td>
                                <td class="py-3 px-4 text-center align-top">
                                    @if($field->is_active)
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
                                <td class="py-3 px-4 text-center align-top">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Edit button -->
                                        <button type="button" @click="openEdit({{ json_encode($field) }})"
                                                class="p-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                                                title="Edit Kolom">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>

                                        <!-- Delete / Deactivate form -->
                                        <form action="{{ route('faskes-fields.destroy', $field->id) }}" method="POST"
                                              onsubmit="return confirm('{{ $field->values_count > 0 ? "Kolom ini memiliki {$field->values_count} nilai faskes. Menghapus akan MENONAKTIFKAN kolom ini tanpa menghapus riwayat nilai. Lanjutkan?" : "Hapus permanen definisi kolom ini?" }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-600 transition"
                                                    title="{{ $field->values_count > 0 ? 'Nonaktifkan Kolom' : 'Hapus Kolom' }}">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-xs">Belum ada kolom tambahan yang dibuat.</p>
                                        <button type="button" @click="createModalOpen = true" class="text-xs text-indigo-600 font-semibold hover:underline">
                                            + Tambah Kolom Pertama
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($fields->hasPages())
                <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
                    {{ $fields->links() }}
                </div>
            @endif
        </div>
    </main>

    <!-- Modal Tambah Kolom Tambahan -->
    <div x-show="createModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-100 relative"
             @click.away="createModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Tambah Kolom Tambahan Baru</h3>
                        <p class="text-[11px] text-slate-400">Admin dapat menambahkan dropdown dinamis</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('faskes-fields.store') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Label Kolom <span class="text-rose-500">*</span></label>
                    <input type="text" name="label" x-model="createField.label" required placeholder="Contoh: Layanan 24 Jam, Akreditasi..."
                           class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Berlaku Untuk</label>
                        <select name="jenis_faskes" x-model="createField.jenis_faskes"
                                class="w-full text-xs px-2.5 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua Jenis (Global)</option>
                            @foreach($validJenisFaskes as $jf)
                                <option value="{{ $jf }}">{{ ucwords(str_replace('_', ' ', $jf)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Tampil</label>
                        <input type="number" name="sort_order" x-model="createField.sort_order" min="0"
                               class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold text-slate-700">Daftar Pilihan Opsi <span class="text-rose-500">*</span></label>
                        <button type="button" @click="addCreateOption()"
                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">
                            + Tambah Opsi
                        </button>
                    </div>
                    <div class="space-y-1.5 max-h-44 overflow-y-auto pr-1">
                        <template x-for="(opt, idx) in createField.options" :key="idx">
                            <div class="flex items-center gap-1.5">
                                <input type="text" name="options[]" x-model="createField.options[idx]" required
                                       :placeholder="'Opsi ' + (idx + 1)"
                                       class="flex-1 text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <button type="button" @click="removeCreateOption(idx)"
                                        x-show="createField.options.length > 1"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <p class="text-[10.5px] text-slate-400 mt-1">Minimal 1 opsi, maksimal 30 opsi per dropdown.</p>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_required" value="1" x-model="createField.is_required"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700 font-medium">Wajib diisi pada form faskes</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="createModalOpen = false"
                                class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                            Simpan Kolom
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kolom Tambahan -->
    <div x-show="editModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-100 relative"
             @click.away="editModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Edit Kolom Tambahan</h3>
                        <p class="text-[11px] text-slate-400">Perbarui label, daftar opsi, atau status aktif</p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form :action="'{{ url('/faskes-fields') }}/' + editField.id" method="POST" class="mt-4 space-y-3.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Label Kolom <span class="text-rose-500">*</span></label>
                    <input type="text" name="label" x-model="editField.label" required
                           class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Berlaku Untuk</label>
                        <select name="jenis_faskes" x-model="editField.jenis_faskes"
                                class="w-full text-xs px-2.5 py-2 border border-slate-200 rounded-lg bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua Jenis (Global)</option>
                            @foreach($validJenisFaskes as $jf)
                                <option value="{{ $jf }}">{{ ucwords(str_replace('_', ' ', $jf)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Tampil</label>
                        <input type="number" name="sort_order" x-model="editField.sort_order" min="0"
                               class="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold text-slate-700">Daftar Pilihan Opsi <span class="text-rose-500">*</span></label>
                        <button type="button" @click="addEditOption()"
                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">
                            + Tambah Opsi
                        </button>
                    </div>
                    <div class="space-y-1.5 max-h-44 overflow-y-auto pr-1">
                        <template x-for="(opt, idx) in editField.options" :key="idx">
                            <div class="flex items-center gap-1.5">
                                <input type="text" name="options[]" x-model="editField.options[idx]" required
                                       :placeholder="'Opsi ' + (idx + 1)"
                                       class="flex-1 text-xs px-2.5 py-1.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <button type="button" @click="removeEditOption(idx)"
                                        x-show="editField.options.length > 1"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_required" value="1" x-model="editField.is_required"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700 font-medium">Wajib diisi</span>
                    </label>

                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" x-model="editField.is_active"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700 font-medium">Aktif</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false"
                            class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-400 mt-auto">
        &copy; {{ date('Y') }} Sistem Informasi Geografis Fasilitas Kesehatan Dinas Kesehatan Kabupaten Banyumas
    </footer>

</body>
</html>
