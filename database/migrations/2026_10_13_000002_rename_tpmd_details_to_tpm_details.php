<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename tpmd_details → tpm_details agar bisa dipakai oleh
     * semua jenis TPM (tpmd, tpmdg, tpmb, tpmp).
     */
    public function up(): void
    {
        if (Schema::hasTable('tpmd_details') && ! Schema::hasTable('tpm_details')) {
            Schema::rename('tpmd_details', 'tpm_details');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tpm_details') && ! Schema::hasTable('tpmd_details')) {
            Schema::rename('tpm_details', 'tpmd_details');
        }
    }
};
