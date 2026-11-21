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

    public function suratDihapus(Surat $surat, User $actor): AktivitasSurat
    {
        $waktu = now();
        $perihal = filled($surat->perihal) ? $surat->perihal : 'Tidak dicantumkan';

        return $this->record(
            $surat,
            $actor,
            AktivitasSurat::TIPE_SURAT_DIHAPUS,
            "{$actor->name} menghapus surat {$surat->nomor_surat} dari daftar aktif.",
            [
                'surat_id' => $surat->id,
                'pegawai_penghapus_id' => $actor->id,
                'nomor_surat' => $surat->nomor_surat,
                'perihal' => $perihal,
                'dihapus_pada' => $waktu->toIso8601String(),
            ],
            $waktu
        );
    }

    public function suratDiambil(Surat $surat, User $actor): AktivitasSurat
    {
        return $this->record(
            $surat,
            $actor,
            AktivitasSurat::TIPE_SURAT_DIAMBIL,
            "{$actor->name} mengambil penanganan surat {$surat->nomor_surat}.",
            ['pegawai_id' => $actor->id]
        );
    }

    public function pengambilanDibatalkan(Surat $surat, User $actor, string $statusSebelumnya): AktivitasSurat
    {
        return $this->record(
            $surat,
            $actor,
            AktivitasSurat::TIPE_PENGAMBILAN_DIBATALKAN,
            "{$actor->name} membatalkan pengambilan surat {$surat->nomor_surat}. Surat kembali tersedia untuk diambil pegawai lain.",
            [
                'pegawai_id' => $actor->id,
                'status_sebelumnya' => $statusSebelumnya,
            ]
        );
    }

    public function statusDiperbarui(Surat $surat, User $actor, ?string $statusSebelumnya = null): AktivitasSurat
    {
        $metadata = [
            'pegawai_id' => $actor->id,
            'status_baru' => $surat->status,
        ];

        if ($statusSebelumnya !== null) {
            $metadata['status_sebelumnya'] = $statusSebelumnya;
        }

        return match ($surat->status) {
            Surat::STATUS_SEDANG_DIPROSES => $this->record(
                $surat,
                $actor,
                AktivitasSurat::TIPE_MULAI_DIPROSES,
                "{$actor->name} mulai memproses surat {$surat->nomor_surat}.",
                $metadata
            ),
            Surat::STATUS_SELESAI => $this->record(
                $surat,
                $actor,
                AktivitasSurat::TIPE_SELESAI,
                "{$actor->name} menyelesaikan surat {$surat->nomor_surat}.",
                $metadata
            ),
            default => throw new \LogicException('Status surat tidak menghasilkan aktivitas progres.'),
        };
    }
}
