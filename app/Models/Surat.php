<?php

namespace App\Models;

use Database\Factories\SuratFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nomor_surat',
    'tanggal_masuk',
    'perihal',
    'pemohon_pengirim',
    'pegawai_id',
    'keterangan',
])]
class Surat extends Model
{
    /** @use HasFactory<SuratFactory> */
    use HasFactory;

    public const STATUS_BELUM_DITANGANI = 'belum_ditangani';

    public const STATUS_SEDANG_DIPROSES = 'sedang_diproses';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_LABELS = [
        self::STATUS_BELUM_DITANGANI => 'Belum Ditangani',
        self::STATUS_SEDANG_DIPROSES => 'Sedang Diproses',
        self::STATUS_SELESAI => 'Selesai',
    ];

    /**
     * Pegawai yang saat ini menangani surat.
     *
     * @return BelongsTo<User, $this>
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pegawai_id');
    }

    /**
     * Riwayat perubahan dan progres surat.
     *
     * @return HasMany<AktivitasSurat, $this>
     */
    public function aktivitas(): HasMany
    {
        return $this->hasMany(AktivitasSurat::class);
    }

    /**
     * Terapkan perubahan administratif sekaligus sinkronkan waktu penugasan.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function applyAdministrativeChanges(array $attributes): void
    {
        $pegawaiLama = $this->exists ? $this->pegawai_id : null;

        $this->fill($attributes);

        if ((string) $pegawaiLama !== (string) $this->pegawai_id) {
            $this->ditugaskan_pada = $this->pegawai_id === null ? null : now();
        }
    }

    public function canAdvanceTo(string $status): bool
    {
        return match ($this->status) {
            self::STATUS_BELUM_DITANGANI => $status === self::STATUS_SEDANG_DIPROSES,
            self::STATUS_SEDANG_DIPROSES => $status === self::STATUS_SELESAI,
            default => false,
        };
    }

    public function advanceStatus(string $status): void
    {
        if (! $this->canAdvanceTo($status)) {
            throw new \LogicException('Transisi status surat tidak diizinkan.');
        }

        $this->status = $status;

        if ($status === self::STATUS_SEDANG_DIPROSES) {
            $this->mulai_diproses_pada = now();
            $this->selesai_pada = null;
        } elseif ($status === self::STATUS_SELESAI) {
            $this->selesai_pada = now();
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'ditugaskan_pada' => 'datetime',
            'mulai_diproses_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }
}
