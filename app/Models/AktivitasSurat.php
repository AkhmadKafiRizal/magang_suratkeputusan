<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['surat_id', 'user_id', 'tipe', 'deskripsi', 'metadata'])]
class AktivitasSurat extends Model
{
    public const TIPE_SURAT_DICATAT = 'surat_dicatat';

    public const TIPE_SURAT_DIEDIT = 'surat_diedit';

    public const TIPE_PEGAWAI_DITUGASKAN = 'pegawai_ditugaskan';

    public const TIPE_PEGAWAI_DIGANTI = 'pegawai_diganti';

    public const TIPE_PENUGASAN_DIHAPUS = 'penugasan_dihapus';

    public const TIPE_MULAI_DIPROSES = 'mulai_diproses';

    public const TIPE_SELESAI = 'selesai';

    public const KATEGORI_FILTER = [
        'pencatatan' => [
            'label' => 'Pencatatan Surat',
            'tipe' => [self::TIPE_SURAT_DICATAT],
        ],
        'perubahan_data' => [
            'label' => 'Perubahan Data',
            'tipe' => [self::TIPE_SURAT_DIEDIT],
        ],
        'penugasan' => [
            'label' => 'Penugasan',
            'tipe' => [
                self::TIPE_PEGAWAI_DITUGASKAN,
                self::TIPE_PEGAWAI_DIGANTI,
                self::TIPE_PENUGASAN_DIHAPUS,
            ],
        ],
        'mulai_diproses' => [
            'label' => 'Mulai Diproses',
            'tipe' => [self::TIPE_MULAI_DIPROSES],
        ],
        'selesai' => [
            'label' => 'Selesai',
            'tipe' => [self::TIPE_SELESAI],
        ],
    ];

    /** @return BelongsTo<Surat, $this> */
    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getTipeLabelAttribute(): string
    {
        return match ($this->tipe) {
            self::TIPE_SURAT_DICATAT => 'Surat Dicatat',
            self::TIPE_SURAT_DIEDIT => 'Surat Diedit',
            self::TIPE_PEGAWAI_DITUGASKAN,
            self::TIPE_PEGAWAI_DIGANTI,
            self::TIPE_PENUGASAN_DIHAPUS => 'Penugasan',
            self::TIPE_MULAI_DIPROSES => 'Mulai Diproses',
            self::TIPE_SELESAI => 'Selesai',
            default => 'Aktivitas Surat',
        };
    }

    public function getTipeBadgeClassAttribute(): string
    {
        return match ($this->tipe) {
            self::TIPE_SURAT_DICATAT => 'dicatat',
            self::TIPE_SURAT_DIEDIT => 'diedit',
            self::TIPE_PEGAWAI_DITUGASKAN,
            self::TIPE_PEGAWAI_DIGANTI,
            self::TIPE_PENUGASAN_DIHAPUS => 'penugasan',
            self::TIPE_MULAI_DIPROSES => 'diproses',
            self::TIPE_SELESAI => 'selesai',
            default => 'netral',
        };
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
