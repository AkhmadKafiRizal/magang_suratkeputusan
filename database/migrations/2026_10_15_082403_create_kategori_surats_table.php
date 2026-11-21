<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_surats', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();
        });

        // Isi 6 kategori awal
        DB::table('kategori_surats')->insert([
            ['nama' => 'Pengurangan PBB',   'slug' => 'pengurangan-pbb',   'urutan' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Pembatalan PBB',    'slug' => 'pembatalan-pbb',    'urutan' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Keberatan PBB',     'slug' => 'keberatan-pbb',     'urutan' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Pengurangan BPHTB', 'slug' => 'pengurangan-bphtb', 'urutan' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'PBJT',              'slug' => 'pbjt',              'urutan' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Surat Lain-lain',   'slug' => 'lain-lain',         'urutan' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_surats');
    }
};
