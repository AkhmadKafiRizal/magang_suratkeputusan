<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplikatAktif = DB::table('surats')
            ->whereNull('deleted_at')
            ->selectRaw('LOWER(TRIM(nomor_surat)) AS nomor_normal, COUNT(*) AS jumlah')
            ->groupByRaw('LOWER(TRIM(nomor_surat))')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplikatAktif) {
            throw new RuntimeException(
                'Migration dibatalkan karena terdapat nomor surat aktif yang identik setelah normalisasi huruf dan spasi.'
            );
        }

        DB::statement(<<<'SQL'
            ALTER TABLE surats
                DROP INDEX surats_nomor_surat_unique,
                ADD COLUMN nomor_surat_aktif VARCHAR(255)
                    GENERATED ALWAYS AS (
                        CASE WHEN deleted_at IS NULL THEN LOWER(TRIM(nomor_surat)) ELSE NULL END
                    ) STORED,
                ADD UNIQUE INDEX surats_nomor_surat_aktif_unique (nomor_surat_aktif)
        SQL);
    }

    public function down(): void
    {
        $duplikat = DB::table('surats')
            ->selectRaw('LOWER(TRIM(nomor_surat)) AS nomor_normal, COUNT(*) AS jumlah')
            ->groupByRaw('LOWER(TRIM(nomor_surat))')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplikat) {
            throw new RuntimeException(
                'Rollback dibatalkan karena terdapat nomor surat yang telah digunakan ulang. '
                .'Hapus duplikasi historis secara terencana sebelum memulihkan indeks unik lama.'
            );
        }

        DB::statement(<<<'SQL'
            ALTER TABLE surats
                DROP INDEX surats_nomor_surat_aktif_unique,
                DROP COLUMN nomor_surat_aktif,
                ADD UNIQUE INDEX surats_nomor_surat_unique (nomor_surat)
        SQL);
    }
};
