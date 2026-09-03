<?php

namespace Database\Factories;

use App\Models\Surat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Surat>
 */
class SuratFactory extends Factory
{
    protected $model = Surat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_surat' => fake()->unique()->numerify('###/ARSIP/2026'),
            'tanggal_masuk' => fake()->dateTimeBetween('-3 months', 'now'),
            'perihal' => fake()->sentence(5),
            'pemohon_pengirim' => fake()->company(),
            'pegawai_id' => null,
            'status' => Surat::STATUS_BELUM_DITANGANI,
            'keterangan' => null,
            'mulai_diproses_pada' => null,
        ];
    }
}
