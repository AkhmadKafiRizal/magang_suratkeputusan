<?php

namespace App\Http\Requests;

use App\Models\Surat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSuratStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $surat = $this->route('surat');

        return $surat instanceof Surat && $this->user()?->can('updateStatus', $surat) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([Surat::STATUS_SEDANG_DIPROSES, Surat::STATUS_SELESAI]),
            ],
            'ditugaskan_pada' => ['prohibited'],
            'mulai_diproses_pada' => ['prohibited'],
            'selesai_pada' => ['prohibited'],
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
                $status = (string) $this->input('status');

                if ($surat instanceof Surat && ! $surat->canAdvanceTo($status)) {
                    $validator->errors()->add(
                        'status',
                        'Perubahan status tidak diizinkan. Progres surat hanya dapat diperbarui secara berurutan.'
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
            'status.required' => 'Status tujuan wajib dikirim.',
            'status.in' => 'Status tujuan tidak valid.',
            'ditugaskan_pada.prohibited' => 'Waktu penugasan diatur otomatis oleh sistem dan tidak dapat diubah manual.',
            'mulai_diproses_pada.prohibited' => 'Waktu mulai proses diatur otomatis oleh sistem dan tidak dapat diubah manual.',
            'selesai_pada.prohibited' => 'Waktu selesai diatur otomatis oleh sistem dan tidak dapat diubah manual.',
        ];
    }
}
