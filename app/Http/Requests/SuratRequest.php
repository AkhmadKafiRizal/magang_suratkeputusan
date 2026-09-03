<?php

namespace App\Http\Requests;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_KEPALA_BIDANG;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nomor_surat' => ['required', 'string', 'max:255'],
            'tanggal_masuk' => ['required', 'date'],
            'perihal' => ['required', 'string', 'max:2000'],
            'pemohon_pengirim' => ['required', 'string', 'max:255'],
            'pegawai_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_PEGAWAI),
            ],
            'status' => ['prohibited'],
            'ditugaskan_pada' => ['prohibited'],
            'mulai_diproses_pada' => ['prohibited'],
            'selesai_pada' => ['prohibited'],
            'keterangan' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $surat = $this->route('surat');

                if ($surat instanceof Surat
                    && $surat->status !== Surat::STATUS_BELUM_DITANGANI
                    && $this->input('pegawai_id') === null) {
                    $validator->errors()->add(
                        'pegawai_id',
                        'Penugasan tidak dapat dihapus karena surat sedang diproses atau sudah selesai.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomor_surat.required' => 'Nomor surat wajib diisi.',
            'tanggal_masuk.required' => 'Tanggal masuk wajib diisi.',
            'tanggal_masuk.date' => 'Tanggal masuk harus berupa tanggal yang valid.',
            'perihal.required' => 'Perihal wajib diisi.',
            'pemohon_pengirim.required' => 'Pemohon / pengirim wajib diisi.',
            'pegawai_id.exists' => 'Pegawai yang dipilih tidak valid atau bukan pengguna dengan role pegawai.',
            'status.prohibited' => 'Status proses hanya dapat diperbarui oleh pegawai yang menangani surat.',
            'ditugaskan_pada.prohibited' => 'Waktu penugasan diatur otomatis oleh sistem dan tidak dapat diubah manual.',
            'mulai_diproses_pada.prohibited' => 'Waktu mulai proses diatur otomatis oleh sistem dan tidak dapat diubah manual.',
            'selesai_pada.prohibited' => 'Waktu selesai diatur otomatis oleh sistem dan tidak dapat diubah manual.',
        ];
    }
}
