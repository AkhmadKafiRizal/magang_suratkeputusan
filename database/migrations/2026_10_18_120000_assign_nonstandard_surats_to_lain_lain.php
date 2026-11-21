<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $kategoriLainLainId = DB::table('kategori_surats')
            ->where('slug', 'lain-lain')
            ->value('id');

        if ($kategoriLainLainId === null) {
            $kategoriLainLainId = DB::table('kategori_surats')->insertGetId([
                'nama' => 'Surat Lain-lain',
                'slug' => 'lain-lain',
                'urutan' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $kategoriResmiIds = DB::table('kategori_surats')
            ->whereIn('slug', [
                'pengurangan-pbb',
                'pembatalan-pbb',
                'keberatan-pbb',
                'pengurangan-bphtb',
                'pbjt',
                'lain-lain',
            ])
            ->pluck('id');

        DB::table('surats')
            ->whereNull('kategori_surat_id')
            ->orWhereNotIn('kategori_surat_id', $kategoriResmiIds)
            ->update([
                'kategori_surat_id' => $kategoriLainLainId,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Pengelompokan ke Surat Lain-lain tidak dibalik agar data tetap valid.
    }
};
