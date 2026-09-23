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
        // Aktifkan extension PostGIS
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');

        // Tabel utama faskes
        Schema::create('faskes', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('jenis_faskes', [
                'puskesmas',
                'rumah_sakit',
                'klinik_pratama',
                'klinik_utama',
                'laboratorium',
                'upkdk',
            ]);
            $table->text('alamat')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('desa')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->geometry('lokasi', subtype: 'point', srid: 4326)->nullable();
            $table->string('nomor_telepon')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();

            $table->spatialIndex('lokasi');
        });

        // Tabel child: puskesmas_details
        Schema::create('puskesmas_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faskes_id')->unique()->constrained('faskes')->cascadeOnDelete();
            $table->enum('kategori', ['rawat_jalan', 'rawat_inap']);
            $table->boolean('poned')->default(false);
            $table->boolean('mampu_salin')->default(false);
            $table->integer('jumlah_tempat_tidur')->default(0);
            $table->boolean('ambulans_transport')->default(false);
            $table->boolean('ambulans_roda_dua')->default(false);
            $table->date('masa_izin')->nullable();
            $table->integer('jumlah_sdm')->default(0);
            $table->enum('wilayah', ['pedesaan', 'perkotaan']);
            $table->timestamps();
        });

        // Tabel child: rumah_sakit_details
        Schema::create('rumah_sakit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faskes_id')->unique()->constrained('faskes')->cascadeOnDelete();
            $table->boolean('ambulans_transport')->default(false);
            $table->boolean('ambulans_gadar')->default(false);
            $table->text('kemampuan_pelayanan')->nullable();
            $table->date('masa_izin')->nullable();
            $table->timestamps();
        });

        // Tabel child: klinik_pratama_details
        Schema::create('klinik_pratama_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faskes_id')->unique()->constrained('faskes')->cascadeOnDelete();
            $table->boolean('ambulans_transport')->default(false);
            $table->date('masa_izin')->nullable();
            $table->text('jenis_layanan')->nullable();
            $table->integer('jumlah_sdm')->default(0);
            $table->integer('bed_rawat_inap')->default(0);
            $table->boolean('bpjs')->default(false);
            $table->string('pj')->nullable();
            $table->string('kontak_pj')->nullable();
            $table->timestamps();
        });

        // Tabel child: klinik_utama_details
        Schema::create('klinik_utama_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faskes_id')->unique()->constrained('faskes')->cascadeOnDelete();
            $table->boolean('ambulans')->default(false);
            $table->date('masa_izin')->nullable();
            $table->text('kemampuan_layanan')->nullable();
            $table->string('kepemilikan')->nullable();
            $table->timestamps();
        });

        // Tabel child: laboratorium_details
        Schema::create('laboratorium_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faskes_id')->unique()->constrained('faskes')->cascadeOnDelete();
            $table->date('masa_izin')->nullable();
            $table->text('jenis_layanan')->nullable();
            $table->string('kepemilikan')->nullable();
            $table->timestamps();
        });

        // Tabel child: upkdk_details
        Schema::create('upkdk_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faskes_id')->unique()->constrained('faskes')->cascadeOnDelete();
            $table->enum('jenis', ['pustu', 'pkd']);
            $table->integer('jumlah_sdm')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upkdk_details');
        Schema::dropIfExists('laboratorium_details');
        Schema::dropIfExists('klinik_utama_details');
        Schema::dropIfExists('klinik_pratama_details');
        Schema::dropIfExists('rumah_sakit_details');
        Schema::dropIfExists('puskesmas_details');
        Schema::dropIfExists('faskes');
    }
};
