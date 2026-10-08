<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Catatan: File migration indeks ini disiapkan untuk optimasi kueri PostgreSQL/PostGIS.
     * Menggunakan klausa IF NOT EXISTS agar sepenuhnya idempotent.
     */
    public function up(): void
    {
        // 1. Indeks Spasial GIST pada kolom lokasi tabel faskes (PostGIS)
        if (Schema::hasTable('faskes') && Schema::hasColumn('faskes', 'lokasi')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_lokasi_gist ON faskes USING GIST (lokasi);');
        }

        // 2. Indeks B-Tree pada kolom filter utama tabel faskes
        if (Schema::hasTable('faskes')) {
            if (Schema::hasColumn('faskes', 'jenis_faskes')) {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_jenis_faskes_btree ON faskes (jenis_faskes);');
            }
            if (Schema::hasColumn('faskes', 'status')) {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_status_btree ON faskes (status);');
            }
            if (Schema::hasColumn('faskes', 'kecamatan')) {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_faskes_kecamatan_btree ON faskes (kecamatan);');
            }
        }

        // 3. Indeks B-Tree pada kolom foreign key faskes_id di tiap tabel detail (jika belum terindeks)
        $detailTables = [
            'puskesmas_details'     => 'idx_puskesmas_details_faskes_id',
            'rumah_sakit_details'   => 'idx_rumah_sakit_details_faskes_id',
            'klinik_pratama_details'=> 'idx_klinik_pratama_details_faskes_id',
            'klinik_utama_details'  => 'idx_klinik_utama_details_faskes_id',
            'laboratorium_details'  => 'idx_laboratorium_details_faskes_id',
            'upkdk_details'         => 'idx_upkdk_details_faskes_id',
            'griya_sehat_details'   => 'idx_griya_sehat_details_faskes_id',
            'faskes_field_values'   => 'idx_faskes_field_values_faskes_id',
        ];

        foreach ($detailTables as $table => $indexName) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'faskes_id')) {
                DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} (faskes_id);");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indeks yang dibuat oleh migration ini
        $indexes = [
            'idx_faskes_lokasi_gist',
            'idx_faskes_jenis_faskes_btree',
            'idx_faskes_status_btree',
            'idx_faskes_kecamatan_btree',
            'idx_puskesmas_details_faskes_id',
            'idx_rumah_sakit_details_faskes_id',
            'idx_klinik_pratama_details_faskes_id',
            'idx_klinik_utama_details_faskes_id',
            'idx_laboratorium_details_faskes_id',
            'idx_upkdk_details_faskes_id',
            'idx_griya_sehat_details_faskes_id',
            'idx_faskes_field_values_faskes_id',
        ];

        foreach ($indexes as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index};");
        }
    }
};
