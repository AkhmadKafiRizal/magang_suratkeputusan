<?php

namespace App\Services;

use App\Models\AktivitasSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Support\Carbon;

class AktivitasSuratService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Surat $surat,
        ?User $actor,
        string $tipe,
        string $deskripsi,
        array $metadata = [],
        ?Carbon $waktu = null
    ): AktivitasSurat {
        $aktivitas = new AktivitasSurat([
            'user_id' => $actor?->id,
            'tipe' => $tipe,
            'deskripsi' => $deskripsi,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        if ($waktu !== null) {
            $aktivitas->created_at = $waktu;
            $aktivitas->updated_at = $waktu;
        }

        $surat->aktivitas()->save($aktivitas);

        return $aktivitas;
    }

    public function suratDicatat(Surat $surat, User $actor): AktivitasSurat
    {
        return $this->record(
            $surat,
            $actor,
            AktivitasSurat::TIPE_SURAT_DICATAT,
            "{$actor->name} mencatat surat {$surat->nomor_surat} ke sistem."
        );
    }

    /** @param array<int, string> $fields */
    public function suratDiedit(Surat $surat, User $actor, array $fields): AktivitasSurat
    {
        return $this->record(
            $surat,
            $actor,
            AktivitasSurat::TIPE_SURAT_DIEDIT,
            "{$actor->name} memperbarui data administratif surat {$surat->nomor_surat}.",
            ['fields' => array_values($fields)]
        );
    }

    public function perubahanPenugasan(
        Surat $surat,
        User $actor,
        ?User $pegawaiLama,
        ?User $pegawaiBaru
    ): AktivitasSurat {
        if ($pegawaiLama === null && $pegawaiBaru !== null) {
            return $this->record(
                $surat,
                $actor,
                AktivitasSurat::TIPE_PEGAWAI_DITUGASKAN,
                "{$actor->name} menugaskan surat {$surat->nomor_surat} kepada {$pegawaiBaru->name}.",
                ['pegawai_baru_id' => $pegawaiBaru->id]
            );
        }

        if ($pegawaiLama !== null && $pegawaiBaru !== null) {
            return $this->record(
                $surat,
                $actor,
                AktivitasSurat::TIPE_PEGAWAI_DIGANTI,
                "{$actor->name} mengganti penanganan surat {$surat->nomor_surat} dari {$pegawaiLama->name} ke {$pegawaiBaru->name}.",
                [
                    'pegawai_lama_id' => $pegawaiLama->id,
                    'pegawai_baru_id' => $pegawaiBaru->id,
                ]
            );
        }

        if ($pegawaiLama !== null) {
            return $this->record(
                $surat,
                $actor,
                AktivitasSurat::TIPE_PENUGASAN_DIHAPUS,
                "{$actor->name} menghapus penugasan {$pegawaiLama->name} dari surat {$surat->nomor_surat}.",
                ['pegawai_lama_id' => $pegawaiLama->id]
            );
        }

        throw new \LogicException('Perubahan penugasan tidak ditemukan.');
    }

    public function statusDiperbarui(Surat $surat, User $actor): AktivitasSurat
    {
        return match ($surat->status) {
            Surat::STATUS_SEDANG_DIPROSES => $this->record(
                $surat,
                $actor,
                AktivitasSurat::TIPE_MULAI_DIPROSES,
                "{$actor->name} mulai memproses surat {$surat->nomor_surat}."
            ),
            Surat::STATUS_SELESAI => $this->record(
                $surat,
                $actor,
                AktivitasSurat::TIPE_SELESAI,
                "{$actor->name} menyelesaikan surat {$surat->nomor_surat}."
            ),
            default => throw new \LogicException('Status surat tidak menghasilkan aktivitas progres.'),
        };
    }
}
