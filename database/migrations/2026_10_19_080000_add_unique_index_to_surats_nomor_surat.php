<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surats', function (Blueprint $table): void {
            $table->unique('nomor_surat', 'surats_nomor_surat_unique');
        });
    }

    public function down(): void
    {
        Schema::table('surats', function (Blueprint $table): void {
            $table->dropUnique('surats_nomor_surat_unique');
        });
    }
};
