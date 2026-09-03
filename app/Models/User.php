<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_KEPALA_BIDANG = 'kepala_bidang';

    public const ROLE_PEGAWAI = 'pegawai';

    /**
     * Surat yang ditangani oleh pengguna sebagai pegawai.
     *
     * @return HasMany<Surat, $this>
     */
    public function suratDitangani(): HasMany
    {
        return $this->hasMany(Surat::class, 'pegawai_id');
    }

    public function scopeWithMonitoringCounts(Builder $query): Builder
    {
        return $query->withCount([
            'suratDitangani as belum_ditangani_count' => fn (Builder $query) => $query->where('status', Surat::STATUS_BELUM_DITANGANI),
            'suratDitangani as sedang_diproses_count' => fn (Builder $query) => $query->where('status', Surat::STATUS_SEDANG_DIPROSES),
            'suratDitangani as selesai_count' => fn (Builder $query) => $query->where('status', Surat::STATUS_SELESAI),
            'suratDitangani as total_ditangani_count',
        ]);
    }

    public function getSuratAktifCountAttribute(): int
    {
        return (int) $this->belum_ditangani_count + (int) $this->sedang_diproses_count;
    }

    public function getBebanKerjaLabelAttribute(): string
    {
        return match (true) {
            $this->surat_aktif_count <= 2 => 'Ringan',
            $this->surat_aktif_count <= 5 => 'Sedang',
            default => 'Tinggi',
        };
    }

    public function getBebanKerjaClassAttribute(): string
    {
        return match ($this->beban_kerja_label) {
            'Ringan' => 'ringan',
            'Sedang' => 'sedang',
            default => 'tinggi',
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
