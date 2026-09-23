<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper: drop default, alter type, set new default
        // PostgreSQL requires dropping the default before changing type

        // 1. Klinik Pratama — ambulans_transport: boolean → integer
        DB::statement("ALTER TABLE klinik_pratama_details ALTER COLUMN ambulans_transport DROP DEFAULT;");
        DB::statement("ALTER TABLE klinik_pratama_details ALTER COLUMN ambulans_transport TYPE integer USING (CASE WHEN ambulans_transport IS TRUE THEN 1 ELSE 0 END);");
        DB::statement("ALTER TABLE klinik_pratama_details ALTER COLUMN ambulans_transport SET DEFAULT 0;");

        // 2. Klinik Utama — ambulans: boolean → integer
        DB::statement("ALTER TABLE klinik_utama_details ALTER COLUMN ambulans DROP DEFAULT;");
        DB::statement("ALTER TABLE klinik_utama_details ALTER COLUMN ambulans TYPE integer USING (CASE WHEN ambulans IS TRUE THEN 1 ELSE 0 END);");
        DB::statement("ALTER TABLE klinik_utama_details ALTER COLUMN ambulans SET DEFAULT 0;");

        // 3. Puskesmas

        // poned: boolean → varchar(20)
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN poned DROP DEFAULT;");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN poned TYPE varchar(20) USING (CASE WHEN poned IS TRUE THEN 'Ya PONED' ELSE 'Tidak PONED' END);");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN poned SET DEFAULT 'Tidak PONED';");

        // mampu_salin: boolean → varchar(10)
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN mampu_salin DROP DEFAULT;");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN mampu_salin TYPE varchar(10) USING (CASE WHEN mampu_salin IS TRUE THEN 'Ya' ELSE 'Tidak' END);");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN mampu_salin SET DEFAULT 'Ya';");

        // ambulans_transport: boolean → integer
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_transport DROP DEFAULT;");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_transport TYPE integer USING (CASE WHEN ambulans_transport IS TRUE THEN 1 ELSE 0 END);");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_transport SET DEFAULT 0;");

        // ambulans_roda_dua: boolean → integer
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_roda_dua DROP DEFAULT;");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_roda_dua TYPE integer USING (CASE WHEN ambulans_roda_dua IS TRUE THEN 1 ELSE 0 END);");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_roda_dua SET DEFAULT 0;");

        // 4. Rumah Sakit

        // ambulans_transport: boolean → integer
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_transport DROP DEFAULT;");
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_transport TYPE integer USING (CASE WHEN ambulans_transport IS TRUE THEN 1 ELSE 0 END);");
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_transport SET DEFAULT 0;");

        // ambulans_gadar: boolean → integer
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_gadar DROP DEFAULT;");
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_gadar TYPE integer USING (CASE WHEN ambulans_gadar IS TRUE THEN 1 ELSE 0 END);");
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_gadar SET DEFAULT 0;");

        // Tambah kolom baru ponek
        Schema::table('rumah_sakit_details', function (Blueprint $table) {
            if (! Schema::hasColumn('rumah_sakit_details', 'ponek')) {
                $table->string('ponek', 20)->default('Tidak PONEK')->after('ambulans_gadar');
            }
        });

        // 5. UPKDK — tambah is_pustu dan is_pkd, buat jenis nullable
        Schema::table('upkdk_details', function (Blueprint $table) {
            if (! Schema::hasColumn('upkdk_details', 'is_pustu')) {
                $table->string('is_pustu', 10)->default('Tidak')->after('faskes_id');
            }
            if (! Schema::hasColumn('upkdk_details', 'is_pkd')) {
                $table->string('is_pkd', 10)->default('Tidak')->after('is_pustu');
            }
        });

        // Migrasi data lama dari jenis ke is_pustu / is_pkd
        if (Schema::hasColumn('upkdk_details', 'jenis')) {
            DB::statement("UPDATE upkdk_details SET is_pustu = 'Ya' WHERE jenis = 'pustu';");
            DB::statement("UPDATE upkdk_details SET is_pkd = 'Ya' WHERE jenis = 'pkd';");
            // Buat jenis nullable agar tidak crash, tapi data lama tetap ada
            DB::statement("ALTER TABLE upkdk_details ALTER COLUMN jenis DROP NOT NULL;");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Klinik Pratama
        DB::statement("ALTER TABLE klinik_pratama_details ALTER COLUMN ambulans_transport DROP DEFAULT;");
        DB::statement("ALTER TABLE klinik_pratama_details ALTER COLUMN ambulans_transport TYPE boolean USING (CASE WHEN ambulans_transport > 0 THEN true ELSE false END);");
        DB::statement("ALTER TABLE klinik_pratama_details ALTER COLUMN ambulans_transport SET DEFAULT false;");

        // 2. Klinik Utama
        DB::statement("ALTER TABLE klinik_utama_details ALTER COLUMN ambulans DROP DEFAULT;");
        DB::statement("ALTER TABLE klinik_utama_details ALTER COLUMN ambulans TYPE boolean USING (CASE WHEN ambulans > 0 THEN true ELSE false END);");
        DB::statement("ALTER TABLE klinik_utama_details ALTER COLUMN ambulans SET DEFAULT false;");

        // 3. Puskesmas
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN poned DROP DEFAULT;");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN poned TYPE boolean USING (CASE WHEN poned = 'Ya PONED' THEN true ELSE false END);");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN poned SET DEFAULT false;");

        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN mampu_salin DROP DEFAULT;");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN mampu_salin TYPE boolean USING (CASE WHEN mampu_salin = 'Ya' THEN true ELSE false END);");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN mampu_salin SET DEFAULT false;");

        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_transport DROP DEFAULT;");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_transport TYPE boolean USING (CASE WHEN ambulans_transport > 0 THEN true ELSE false END);");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_transport SET DEFAULT false;");

        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_roda_dua DROP DEFAULT;");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_roda_dua TYPE boolean USING (CASE WHEN ambulans_roda_dua > 0 THEN true ELSE false END);");
        DB::statement("ALTER TABLE puskesmas_details ALTER COLUMN ambulans_roda_dua SET DEFAULT false;");

        // 4. Rumah Sakit
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_transport DROP DEFAULT;");
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_transport TYPE boolean USING (CASE WHEN ambulans_transport > 0 THEN true ELSE false END);");
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_transport SET DEFAULT false;");

        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_gadar DROP DEFAULT;");
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_gadar TYPE boolean USING (CASE WHEN ambulans_gadar > 0 THEN true ELSE false END);");
        DB::statement("ALTER TABLE rumah_sakit_details ALTER COLUMN ambulans_gadar SET DEFAULT false;");

        Schema::table('rumah_sakit_details', function (Blueprint $table) {
            $table->dropColumn('ponek');
        });

        // 5. UPKDK
        Schema::table('upkdk_details', function (Blueprint $table) {
            $table->dropColumn(['is_pustu', 'is_pkd']);
        });
    }
};
