<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surats', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat');
            $table->date('tanggal_masuk');
            $table->text('perihal');
            $table->string('pemohon_pengirim');
            $table->foreignId('pegawai_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status', 30)->default('belum_ditangani');
            $table->text('keterangan')->nullable();
            $table->timestamp('ditugaskan_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamps();

            $table->index(['status', 'tanggal_masuk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surats');
    }
};
