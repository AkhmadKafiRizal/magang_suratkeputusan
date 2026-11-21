<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom kategori_surat_id ke tabel surats.
     * Nullable agar data surat lama tidak rusak.
     */
    public function up(): void
    {
        Schema::table('surats', function (Blueprint $table) {
            $table->foreignId('kategori_surat_id')
                ->nullable()
                ->after('pemohon_pengirim')
                ->constrained('kategori_surats')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surats', function (Blueprint $table) {
            $table->dropForeign(['kategori_surat_id']);
            $table->dropColumn('kategori_surat_id');
        });
    }
};
