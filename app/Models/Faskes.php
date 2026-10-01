<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Faskes extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'faskes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'jenis_faskes',
        'alamat',
        'kecamatan',
        'desa',
        'latitude',
        'longitude',
        'lokasi',
        'nomor_telepon',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'lokasi',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'detail',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude'  => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * Booted model events: otomatis sinkronisasi kolom geometri PostGIS 'lokasi'.
     *
     * FIX KEAMANAN & AKURASI:
     * - Kolom 'lokasi' WAJIB disinkronkan ulang setiap kali latitude/longitude berubah
     *   (bukan hanya saat record pertama kali dibuat). Sebelumnya kondisi `empty($faskes->lokasi)`
     *   menyebabkan koordinat baru hasil edit tidak pernah tersimpan ke 'lokasi', sehingga semua
     *   query jarak (yang mengutamakan 'lokasi' via COALESCE) memakai titik LAMA yang sudah basi.
     * - Nilai latitude/longitude di-cast eksplisit ke float sebelum disisipkan ke raw SQL,
     *   untuk mencegah SQL injection melalui interpolasi string (meski nilainya sudah melalui
     *   Eloquent cast decimal, defense-in-depth tetap diterapkan di sini).
     */
    protected static function booted(): void
    {
        static::saving(function (Faskes $faskes) {
            $hasCoordinates = $faskes->latitude !== null && $faskes->longitude !== null;
            $needsSync = $faskes->isDirty('latitude') || $faskes->isDirty('longitude') || empty($faskes->lokasi);

            if ($hasCoordinates && $needsSync) {
                $lat = (float) $faskes->latitude;
                $lng = (float) $faskes->longitude;

                // Guard tambahan: pastikan nilai dalam rentang koordinat yang valid
                // sebelum ditulis sebagai raw SQL.
                if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                    $faskes->lokasi = DB::raw(
                        'ST_SetSRID(ST_MakePoint(' . $lng . ', ' . $lat . '), 4326)'
                    );
                }
            }
        });
    }

    /**
     * Scope untuk menghitung jarak lurus (dalam meter) menggunakan ST_DistanceSphere.
     * Selalu memakai kolom geometri 'lokasi' yang sudah tersinkron via event saving().
     */
    public function scopeWithDistance($query, float $latitude, float $longitude)
    {
        return $query->select('faskes.*')
            ->selectRaw(
                'ST_DistanceSphere(
                    COALESCE(faskes.lokasi, ST_SetSRID(ST_MakePoint(faskes.longitude, faskes.latitude), 4326)),
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)
                ) AS jarak_lurus_meter',
                [$longitude, $latitude]
            );
    }

    /**
     * Scope untuk memfilter faskes dalam radius tertentu (dalam kilometer).
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, float $radiusKm)
    {
        $radiusMeters = $radiusKm * 1000;

        return $query->whereRaw(
            'ST_DistanceSphere(
                COALESCE(faskes.lokasi, ST_SetSRID(ST_MakePoint(faskes.longitude, faskes.latitude), 4326)),
                ST_SetSRID(ST_MakePoint(?, ?), 4326)
            ) <= ?',
            [$longitude, $latitude, $radiusMeters]
        );
    }

    /**
     * Relasi hasOne ke detail Puskesmas.
     */
    public function puskesmasDetail(): HasOne
    {
        return $this->hasOne(PuskesmasDetail::class, 'faskes_id');
    }

    /**
     * Relasi hasOne ke detail Rumah Sakit.
     */
    public function rumahSakitDetail(): HasOne
    {
        return $this->hasOne(RumahSakitDetail::class, 'faskes_id');
    }

    /**
     * Relasi hasOne ke detail Klinik Pratama.
     */
    public function klinikPratamaDetail(): HasOne
    {
        return $this->hasOne(KlinikPratamaDetail::class, 'faskes_id');
    }

    /**
     * Relasi hasOne ke detail Klinik Utama.
     */
    public function klinikUtamaDetail(): HasOne
    {
        return $this->hasOne(KlinikUtamaDetail::class, 'faskes_id');
    }

    /**
     * Relasi hasOne ke detail Laboratorium.
     */
    public function laboratoriumDetail(): HasOne
    {
        return $this->hasOne(LaboratoriumDetail::class, 'faskes_id');
    }

    /**
     * Relasi hasOne ke detail UPKDK.
     */
    public function upkdkDetail(): HasOne
    {
        return $this->hasOne(UpkdkDetail::class, 'faskes_id');
    }

    /**
     * Relasi hasOne ke detail Griya Sehat.
     */
    public function griyaSehatDetail(): HasOne
    {
        return $this->hasOne(GriyaSehatDetail::class, 'faskes_id');
    }

    /**
     * Relasi hasMany ke nilai kolom tambahan (custom fields).
     */
    public function fieldValues(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FaskesFieldValue::class, 'faskes_id');
    }

    /**
     * Helper accessor untuk mengambil model detail aktif sesuai jenis_faskes.
     */
    public function getDetailAttribute(): ?Model
    {
        return match ($this->jenis_faskes) {
            'puskesmas'      => $this->puskesmasDetail,
            'rumah_sakit'    => $this->rumahSakitDetail,
            'klinik_pratama' => $this->klinikPratamaDetail,
            'klinik_utama'   => $this->klinikUtamaDetail,
            'laboratorium'   => $this->laboratoriumDetail,
            'upkdk'          => $this->upkdkDetail,
            'griya_sehat'    => $this->griyaSehatDetail,
            default          => null,
        };
    }
}