<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')
                ->constrained('surats')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('tipe', 50);
            $table->text('deskripsi');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tipe', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['surat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_surats');
    }
};
