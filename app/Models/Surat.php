<?php

namespace App\Models;

use Database\Factories\SuratFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nomor_surat',
    'tanggal_masuk',
    'pemohon_pengirim',
    'kategori_surat_id',
    'keterangan',
])]
class Surat extends Model
{
    /** @use HasFactory<SuratFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_BELUM_DITANGANI = 'belum_ditangani';

    public const STATUS_SEDANG_DIPROSES = 'sedang_diproses';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_LABELS = [
        self::STATUS_BELUM_DITANGANI => 'Belum Ditangani',
        self::STATUS_SEDANG_DIPROSES => 'Dalam Proses',
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
     * Kategori / jenis surat.
     *
     * @return BelongsTo<KategoriSurat, $this>
     */
    public function kategoriSurat(): BelongsTo
    {
        return $this->belongsTo(KategoriSurat::class);
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

    public function scopeDalamKategori(Builder $query, string $filter): Builder
    {
        if ($filter === KategoriSurat::SLUG_LAIN_LAIN) {
            return $query->where(function (Builder $query): void {
                $query->whereNull('kategori_surat_id')
                    ->orWhereDoesntHave('kategoriSurat')
                    ->orWhereHas(
                        'kategoriSurat',
                        fn (Builder $query) => $query->whereNotIn('slug', KategoriSurat::SLUG_KATEGORI_UTAMA)
                    );
            });
        }

        if (ctype_digit($filter)) {
            $kategori = KategoriSurat::query()->find((int) $filter);

            if (! $kategori || ! in_array($kategori->slug, KategoriSurat::SLUG_KATEGORI_UTAMA, true)) {
                return $query->dalamKategori(KategoriSurat::SLUG_LAIN_LAIN);
            }

            return $query->where('kategori_surat_id', $kategori->id);
        }

        if (! in_array($filter, KategoriSurat::SLUG_KATEGORI_UTAMA, true)) {
            return $query->dalamKategori(KategoriSurat::SLUG_LAIN_LAIN);
        }

        return $query->whereHas(
            'kategoriSurat',
            fn (Builder $query) => $query->where('slug', $filter)
        );
    }

    /** @param array<string, mixed> $attributes */
    public function applyAdministrativeChanges(array $attributes): void
    {
        $this->fill(collect($attributes)->only([
            'nomor_surat',
            'tanggal_masuk',
            'pemohon_pengirim',
            'kategori_surat_id',
            'keterangan',
        ])->all());
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

    public function getKategoriLabelAttribute(): string
    {
        $kategori = $this->kategoriSurat;

        if ($kategori && in_array($kategori->slug, KategoriSurat::SLUG_KATEGORI_UTAMA, true)) {
            return $kategori->nama;
        }

        return 'Surat Lain-lain';
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
