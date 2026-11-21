<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @return Attribute<string, string> */
    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => strtolower(trim($value)),
        );
    }

    /**
     * Cari akun dengan pakek username atau email tanpa membedakan huruf besar-kecil
     * dan tanpa terpengaruh spasi pada data akun lama.
     */
    public function scopeForLoginIdentity(Builder $query, string $identity): Builder
    {
        $identity = Str::lower(trim($identity));

        return $query->where(function (Builder $query) use ($identity): void {
            $query->whereRaw('LOWER(TRIM(username)) = ?', [$identity])
                ->orWhereRaw('LOWER(TRIM(email)) = ?', [$identity]);
        });
    }

    /**
     * Surat yang ditangani oleh pengguna sebagai pegawai.
     *
     * @return HasMany<Surat, $this>
     */
    public function suratDitangani(): HasMany
    {
        return $this->hasMany(Surat::class, 'pegawai_id');
    }

    /**
     * Aktivitas surat yang dilakukan pengguna sebagai actor.
     *
     * @return HasMany<AktivitasSurat, $this>
     */
    public function aktivitasSurat(): HasMany
    {
        return $this->hasMany(AktivitasSurat::class, 'user_id');
    }

    public function passwordResetOtps(): HasMany
    {
        return $this->hasMany(PasswordResetOtp::class);
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
