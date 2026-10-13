<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel tpmd_details untuk menyimpan masa berlaku SIP Tempat Praktik Mandiri Dokter.
     * Idempotent: cek keberadaan tabel dan kolom sebelum membuat.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tpmd_details')) {
            Schema::create('tpmd_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('faskes_id')
                      ->constrained('faskes')
                      ->onDelete('cascade');
                $table->date('masa_sip')->nullable()->comment('Masa berlaku Surat Izin Praktik (SIP) dokter');
                $table->timestamps();

                $table->unique('faskes_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpmd_details');
    }
};
