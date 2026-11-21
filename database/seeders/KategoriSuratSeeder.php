<?php

namespace Database\Seeders;

use App\Models\KategoriSurat;
use Illuminate\Database\Seeder;

class KategoriSuratSeeder extends Seeder
{
    /**
     * Jalankan seeder master kategori surat.
     * Menggunakan firstOrCreate agar aman dijalankan berulang kali tanpa duplikasi.
     */
    public function run(): void
    {
        $kategoris = [
            ['nama' => 'Pengurangan PBB',   'slug' => 'pengurangan-pbb',   'urutan' => 1],
            ['nama' => 'Pembatalan PBB',    'slug' => 'pembatalan-pbb',    'urutan' => 2],
            ['nama' => 'Keberatan PBB',     'slug' => 'keberatan-pbb',     'urutan' => 3],
            ['nama' => 'Pengurangan BPHTB', 'slug' => 'pengurangan-bphtb', 'urutan' => 4],
            ['nama' => 'PBJT',              'slug' => 'pbjt',              'urutan' => 5],
            ['nama' => 'Surat Lain-lain',   'slug' => 'lain-lain',         'urutan' => 6],
        ];

        foreach ($kategoris as $kategori) {
            KategoriSurat::query()->updateOrCreate(
                ['slug' => $kategori['slug']],
                $kategori
            );
        }
    }
}
