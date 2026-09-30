{{-- Pesan gagal/sukses dari controller (error validasi sudah ditampilkan di halaman form) --}}
@if (session('error'))
    <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs shadow-sm">
        <div class="flex items-start gap-2">
            <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-circle-exclamation text-red-600 text-xs"></i>
            </div>
            <div>
                <div class="font-bold mb-1">Gagal menyimpan:</div>
                <div class="break-words">{{ session('error') }}</div>
            </div>
        </div>
    </div>
@endif

@if (session('success'))
    <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-xs shadow-sm">
        {{ session('success') }}
    </div>
@endif