<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat index idempotent menggunakan CREATE INDEX IF NOT EXISTS untuk kolom yang
     * sering dipakai filter, agregasi, pengurutan dashboard, dan pemantauan masa_izin (Tugas 10A).
     */
    public function up(): void
    {
        // 1. Index pada tabel master faskes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_jenis_faskes ON faskes (jenis_faskes)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_status ON faskes (status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_kecamatan ON faskes (kecamatan)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_jenis_status ON faskes (jenis_faskes, status)');

        // 2. Index pada kolom masa_izin di masing-masing tabel detail
        DB::statement('CREATE INDEX IF NOT EXISTS idx_puskesmas_details_masa_izin ON puskesmas_details (masa_izin)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_rumah_sakit_details_masa_izin ON rumah_sakit_details (masa_izin)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_klinik_pratama_details_masa_izin ON klinik_pratama_details (masa_izin)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_klinik_utama_details_masa_izin ON klinik_utama_details (masa_izin)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_laboratorium_details_masa_izin ON laboratorium_details (masa_izin)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_griya_sehat_details_masa_izin ON griya_sehat_details (masa_izin)');
    }

    /**
     * Reverse the migrations.
     * Menghapus HANYA index yang dibuat oleh migrasi ini secara aman.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_faskes_jenis_faskes');
        DB::statement('DROP INDEX IF EXISTS idx_faskes_status');
        DB::statement('DROP INDEX IF EXISTS idx_faskes_kecamatan');
        DB::statement('DROP INDEX IF EXISTS idx_faskes_jenis_status');

        DB::statement('DROP INDEX IF EXISTS idx_puskesmas_details_masa_izin');
        DB::statement('DROP INDEX IF EXISTS idx_rumah_sakit_details_masa_izin');
        DB::statement('DROP INDEX IF EXISTS idx_klinik_pratama_details_masa_izin');
        DB::statement('DROP INDEX IF EXISTS idx_klinik_utama_details_masa_izin');
        DB::statement('DROP INDEX IF EXISTS idx_laboratorium_details_masa_izin');
        DB::statement('DROP INDEX IF EXISTS idx_griya_sehat_details_masa_izin');
    }
};
