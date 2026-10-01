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
        // 1. Perluas CHECK constraint jenis_faskes pada tabel faskes
        // Hapus constraint lama jika ada, lalu buat constraint baru dengan 5 jenis faskes baru
        DB::statement("ALTER TABLE faskes DROP CONSTRAINT IF EXISTS faskes_jenis_faskes_check;");
        DB::statement("ALTER TABLE faskes ADD CONSTRAINT faskes_jenis_faskes_check CHECK (((jenis_faskes)::text = ANY ((ARRAY['puskesmas'::character varying, 'rumah_sakit'::character varying, 'klinik_pratama'::character varying, 'klinik_utama'::character varying, 'laboratorium'::character varying, 'upkdk'::character varying, 'griya_sehat'::character varying, 'tpmd'::character varying, 'tpmdg'::character varying, 'tpmb'::character varying, 'tpmp'::character varying])::text[])));");

        // 2. Klinik Pratama Details
        Schema::table('klinik_pratama_details', function (Blueprint $table) {
            if (! Schema::hasColumn('klinik_pratama_details', 'kategori_layanan')) {
                $table->string('kategori_layanan', 50)->nullable()->after('masa_izin');
            }
            if (! Schema::hasColumn('klinik_pratama_details', 'kepemilikan')) {
                $table->string('kepemilikan', 50)->nullable()->after('bpjs');
            }
        });

        // 3. Klinik Utama Details
        Schema::table('klinik_utama_details', function (Blueprint $table) {
            if (! Schema::hasColumn('klinik_utama_details', 'kategori_layanan')) {
                $table->string('kategori_layanan', 50)->nullable()->after('masa_izin');
            }
            if (! Schema::hasColumn('klinik_utama_details', 'bed_rawat_inap')) {
                $table->integer('bed_rawat_inap')->default(0)->after('kemampuan_layanan');
            }
            if (! Schema::hasColumn('klinik_utama_details', 'bpjs')) {
                $table->boolean('bpjs')->default(false)->after('bed_rawat_inap');
            }
            if (! Schema::hasColumn('klinik_utama_details', 'pj')) {
                $table->string('pj')->nullable()->after('kepemilikan');
            }
            if (! Schema::hasColumn('klinik_utama_details', 'kontak_pj')) {
                $table->string('kontak_pj')->nullable()->after('pj');
            }
        });

        // 4. Rumah Sakit Details
        Schema::table('rumah_sakit_details', function (Blueprint $table) {
            if (! Schema::hasColumn('rumah_sakit_details', 'tipe_rs')) {
                $table->string('tipe_rs', 50)->nullable()->after('kemampuan_pelayanan');
            }
        });

        // 5. Tabel Child Baru: griya_sehat_details
        if (! Schema::hasTable('griya_sehat_details')) {
            Schema::create('griya_sehat_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('faskes_id')->unique()->constrained('faskes')->cascadeOnDelete();
                $table->date('masa_izin')->nullable();
                $table->string('pj')->nullable();
                $table->string('kontak_pj')->nullable();
                $table->integer('jumlah_sdm')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('griya_sehat_details');

        Schema::table('rumah_sakit_details', function (Blueprint $table) {
            if (Schema::hasColumn('rumah_sakit_details', 'tipe_rs')) {
                $table->dropColumn('tipe_rs');
            }
        });

        Schema::table('klinik_utama_details', function (Blueprint $table) {
            $cols = array_filter(['kategori_layanan', 'bed_rawat_inap', 'bpjs', 'pj', 'kontak_pj'], fn($c) => Schema::hasColumn('klinik_utama_details', $c));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('klinik_pratama_details', function (Blueprint $table) {
            $cols = array_filter(['kategori_layanan', 'kepemilikan'], fn($c) => Schema::hasColumn('klinik_pratama_details', $c));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        DB::statement("ALTER TABLE faskes DROP CONSTRAINT IF EXISTS faskes_jenis_faskes_check;");
        DB::statement("ALTER TABLE faskes ADD CONSTRAINT faskes_jenis_faskes_check CHECK (((jenis_faskes)::text = ANY ((ARRAY['puskesmas'::character varying, 'rumah_sakit'::character varying, 'klinik_pratama'::character varying, 'klinik_utama'::character varying, 'laboratorium'::character varying, 'upkdk'::character varying])::text[])));");
    }
};
