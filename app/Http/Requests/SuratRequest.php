<?php

namespace App\Http\Requests;

use App\Models\KategoriSurat;
use App\Models\Surat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $surat = $this->route('surat');
        $suratId = $surat instanceof Surat ? $surat->getKey() : null;

        return [
            'nomor_surat' => [
                'bail',
                'required',
                'string',
                'max:255',
                'regex:/\A[0-9.]+\z/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && $value !== '' && preg_match('/[0-9]/', $value) !== 1) {
                        $fail('Nomor surat wajib mengandung setidaknya satu angka.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail) use ($suratId): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $nomorSurat = mb_strtolower(trim($value));
                    $sudahAda = Surat::query()
                        ->withTrashed()
                        ->whereRaw('LOWER(TRIM(nomor_surat)) = ?', [$nomorSurat])
                        ->when($suratId !== null, fn ($query) => $query->where('id', '!=', $suratId))
                        ->exists();

                    if ($sudahAda) {
                        $fail('Nomor surat sudah ada. Koreksi kembali input nomor surat Anda.');
                    }
                },
            ],
            'tanggal_masuk' => ['required', 'date'],
            'pemohon_pengirim' => [
                'bail',
                'required',
                'string',
                'max:255',
                "regex:/\A[\p{L}0-9 .,'’&()\/\-]+\z/u",
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && $value !== '' && preg_match('/\p{L}/u', $value) !== 1) {
                        $fail('Pemohon/Pengirim harus berisi nama orang atau badan usaha yang valid, bukan hanya angka atau simbol.');
                    }
                },
            ],
            'kategori_surat_id' => [
                'required',
                'integer',
                Rule::exists('kategori_surats', 'id')
                    ->where(fn ($query) => $query->whereIn('slug', KategoriSurat::SLUG_KATEGORI_RESMI)),
            ],
            'pegawai_id' => ['missing'],
            'status' => ['prohibited'],
            'ditugaskan_pada' => ['prohibited'],
            'mulai_diproses_pada' => ['prohibited'],
            'selesai_pada' => ['prohibited'],
            'keterangan' => ['nullable', 'string', 'max:10000'],
            'submit_action' => ['sometimes', 'string', Rule::in(['save', 'save_and_add'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomor_surat.required' => 'Nomor surat wajib diisi.',
            'nomor_surat.regex' => 'Nomor surat hanya boleh berisi angka dan tanda titik (.).',
            'tanggal_masuk.required' => 'Tanggal masuk wajib diisi.',
            'tanggal_masuk.date' => 'Tanggal masuk harus berupa tanggal yang valid.',
            'pemohon_pengirim.required' => 'Pemohon / pengirim wajib diisi.',
            'pemohon_pengirim.regex' => 'Pemohon/Pengirim mengandung karakter yang tidak diperbolehkan. Gunakan huruf, angka, spasi, dan tanda baca nama yang wajar.',
            'kategori_surat_id.required' => 'Kategori surat wajib dipilih.',
            'kategori_surat_id.exists' => 'Kategori surat yang dipilih tidak valid.',
            'pegawai_id.missing' => 'Penangan surat tidak dapat dipilih melalui formulir ini. Setiap pegawai harus mengambil sendiri surat melalui menu Ambil Surat.',
            'status.prohibited' => 'Status proses hanya dapat diperbarui oleh pegawai yang menangani surat.',
            'ditugaskan_pada.prohibited' => 'Waktu pengambilan surat diatur otomatis oleh sistem dan tidak dapat diubah manual.',
            'mulai_diproses_pada.prohibited' => 'Waktu mulai proses diatur otomatis oleh sistem dan tidak dapat diubah manual.',
            'selesai_pada.prohibited' => 'Waktu selesai diatur otomatis oleh sistem dan tidak dapat diubah manual.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nomor_surat')) {
            $this->merge([
                'nomor_surat' => trim((string) $this->input('nomor_surat')),
            ]);
        }
    }
}
