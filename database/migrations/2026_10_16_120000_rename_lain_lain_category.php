<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kategori_surats')
            ->where('slug', 'lain-lain')
            ->update(['nama' => 'Surat Lain-lain', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('kategori_surats')
            ->where('slug', 'lain-lain')
            ->update(['nama' => 'Lain-lain', 'updated_at' => now()]);
    }
};
