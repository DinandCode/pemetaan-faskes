<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KlinikPratamaDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'klinik_pratama_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'faskes_id',
        'ambulans_transport',
        'masa_izin',
        'jenis_layanan',
        'jumlah_sdm',
        'bed_rawat_inap',
        'bpjs',
        'pj',
        'kontak_pj',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ambulans_transport' => 'integer',
            'bpjs' => 'boolean',
            'masa_izin' => 'date',
            'jumlah_sdm' => 'integer',
            'bed_rawat_inap' => 'integer',
        ];
    }

    /**
     * Relasi belongsTo ke Faskes.
     */
    public function faskes(): BelongsTo
    {
        return $this->belongsTo(Faskes::class, 'faskes_id');
    }
}
