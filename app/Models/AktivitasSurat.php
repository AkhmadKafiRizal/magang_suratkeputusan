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

    public const TIPE_SURAT_DIHAPUS = 'surat_dihapus';

    /** Nilai lama dipertahankan agar riwayat yang sudah tersimpan tetap dapat dibaca. */
    public const TIPE_PENANGAN_MULAI = 'pegawai_ditugaskan';

    public const TIPE_SURAT_DIAMBIL = 'surat_diambil';

    public const TIPE_PENANGAN_DIGANTI = 'pegawai_diganti';

    public const TIPE_PENGAMBILAN_DIBATALKAN = 'penugasan_dihapus';

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
        'penghapusan' => [
            'label' => 'Penghapusan Surat',
            'tipe' => [self::TIPE_SURAT_DIHAPUS],
        ],
        'pengambilan' => [
            'label' => 'Pengambilan Surat',
            'tipe' => [
                self::TIPE_PENANGAN_MULAI,
                self::TIPE_SURAT_DIAMBIL,
                self::TIPE_PENANGAN_DIGANTI,
                self::TIPE_PENGAMBILAN_DIBATALKAN,
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
        return $this->belongsTo(Surat::class)->withTrashed();
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
            self::TIPE_SURAT_DIHAPUS => 'Surat Dihapus',
            self::TIPE_PENANGAN_MULAI,
            self::TIPE_SURAT_DIAMBIL,
            self::TIPE_PENANGAN_DIGANTI,
            self::TIPE_PENGAMBILAN_DIBATALKAN => 'Pengambilan Surat',
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
            self::TIPE_SURAT_DIHAPUS => 'dihapus',
            self::TIPE_PENANGAN_MULAI,
            self::TIPE_SURAT_DIAMBIL,
            self::TIPE_PENANGAN_DIGANTI,
            self::TIPE_PENGAMBILAN_DIBATALKAN => 'pengambilan',
            self::TIPE_MULAI_DIPROSES => 'diproses',
            self::TIPE_SELESAI => 'selesai',
            default => 'netral',
        };
    }

    /**
     * Menetralkan istilah lama dari catatan yang sudah tersimpan.
     */
    public function getDeskripsiTampilanAttribute(): string
    {
        $nomorSurat = $this->surat?->nomor_surat ?? 'tidak tersedia';
        $namaPengguna = $this->actor?->name ?? 'Seorang pegawai';

        return match ($this->tipe) {
            self::TIPE_PENANGAN_MULAI => "{$namaPengguna} mulai menangani surat {$nomorSurat}.",
            self::TIPE_PENANGAN_DIGANTI => "Penangan surat {$nomorSurat} diperbarui.",
            self::TIPE_PENGAMBILAN_DIBATALKAN => "{$namaPengguna} membatalkan pengambilan surat {$nomorSurat}. Surat kembali tersedia untuk diambil pegawai lain.",
            default => $this->deskripsi,
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
