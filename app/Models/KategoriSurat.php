<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriSurat extends Model
{
    protected $fillable = ['nama', 'slug', 'urutan'];

    /**
     * Daftar kategori yang tersedia secara statis untuk digunakan di konstanta.
     * Nilai ini harus konsisten dengan data di tabel kategori_surats.
     */
    public const SLUG_PENGURANGAN_PBB = 'pengurangan-pbb';

    public const SLUG_PEMBATALAN_PBB = 'pembatalan-pbb';

    public const SLUG_KEBERATAN_PBB = 'keberatan-pbb';

    public const SLUG_PENGURANGAN_BPHTB = 'pengurangan-bphtb';

    public const SLUG_PBJT = 'pbjt';

    public const SLUG_LAIN_LAIN = 'lain-lain';

    /** @var list<string> */
    public const SLUG_KATEGORI_UTAMA = [
        self::SLUG_PENGURANGAN_PBB,
        self::SLUG_PEMBATALAN_PBB,
        self::SLUG_KEBERATAN_PBB,
        self::SLUG_PENGURANGAN_BPHTB,
        self::SLUG_PBJT,
    ];

    /** @var list<string> */
    public const SLUG_KATEGORI_RESMI = [
        ...self::SLUG_KATEGORI_UTAMA,
        self::SLUG_LAIN_LAIN,
    ];

    public function scopeResmi(Builder $query): Builder
    {
        return $query->whereIn('slug', self::SLUG_KATEGORI_RESMI);
    }

    /**
     * Surat yang masuk ke kategori ini.
     *
     * @return HasMany<Surat, $this>
     */
    public function surats(): HasMany
    {
        return $this->hasMany(Surat::class);
    }
}
