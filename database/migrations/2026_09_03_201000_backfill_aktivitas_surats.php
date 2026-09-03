<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $namaPengguna = DB::table('users')->pluck('name', 'id');

        DB::table('surats')
            ->select([
                'id',
                'nomor_surat',
                'pegawai_id',
                'created_at',
                'ditugaskan_pada',
                'mulai_diproses_pada',
                'selesai_pada',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($surats) use ($namaPengguna): void {
                foreach ($surats as $surat) {
                    $pegawaiId = $surat->pegawai_id !== null
                        && $namaPengguna->has($surat->pegawai_id)
                            ? (int) $surat->pegawai_id
                            : null;
                    $namaPegawai = $pegawaiId !== null
                        ? (string) $namaPengguna->get($pegawaiId)
                        : 'pegawai terkait';
                    $aktivitas = [];

                    if ($surat->created_at !== null) {
                        $aktivitas[] = $this->aktivitas(
                            (int) $surat->id,
                            null,
                            'surat_dicatat',
                            "Surat {$surat->nomor_surat} dicatat ke sistem.",
                            $surat->created_at
                        );
                    }

                    if ($surat->ditugaskan_pada !== null) {
                        $aktivitas[] = $this->aktivitas(
                            (int) $surat->id,
                            null,
                            'pegawai_ditugaskan',
                            "Surat {$surat->nomor_surat} ditugaskan kepada {$namaPegawai}.",
                            $surat->ditugaskan_pada,
                            ['pegawai_baru_id' => $pegawaiId]
                        );
                    }

                    if ($surat->mulai_diproses_pada !== null) {
                        $aktivitas[] = $this->aktivitas(
                            (int) $surat->id,
                            $pegawaiId,
                            'mulai_diproses',
                            "{$namaPegawai} mulai memproses surat {$surat->nomor_surat}.",
                            $surat->mulai_diproses_pada
                        );
                    }

                    if ($surat->selesai_pada !== null) {
                        $aktivitas[] = $this->aktivitas(
                            (int) $surat->id,
                            $pegawaiId,
                            'selesai',
                            "{$namaPegawai} menyelesaikan surat {$surat->nomor_surat}.",
                            $surat->selesai_pada
                        );
                    }

                    if ($aktivitas !== []) {
                        DB::table('aktivitas_surats')->insert($aktivitas);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('aktivitas_surats')
            ->where('metadata', 'like', '%backfill_fitur_5%')
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function aktivitas(
        int $suratId,
        ?int $userId,
        string $tipe,
        string $deskripsi,
        string $waktu,
        array $metadata = []
    ): array {
        return [
            'surat_id' => $suratId,
            'user_id' => $userId,
            'tipe' => $tipe,
            'deskripsi' => $deskripsi,
            'metadata' => json_encode(
                ['sumber' => 'backfill_fitur_5', ...$metadata],
                JSON_THROW_ON_ERROR
            ),
            'created_at' => $waktu,
            'updated_at' => $waktu,
        ];
    }
};
