<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuskesmasDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'puskesmas_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'faskes_id',
        'kategori',
        'poned',
        'mampu_salin',
        'jumlah_tempat_tidur',
        'ambulans_transport',
        'ambulans_roda_dua',
        'masa_izin',
        'jumlah_sdm',
        'wilayah',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'poned' => 'string',
            'mampu_salin' => 'string',
            'ambulans_transport' => 'integer',
            'ambulans_roda_dua' => 'integer',
            'jumlah_tempat_tidur' => 'integer',
            'jumlah_sdm' => 'integer',
            'masa_izin' => 'date',
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
