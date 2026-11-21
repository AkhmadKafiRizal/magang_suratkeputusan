<?php

namespace App\Http\Requests;

use App\Models\Surat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status tujuan wajib dikirim.',
            'status.in' => 'Status tujuan tidak valid.',
            'ditugaskan_pada.prohibited' => 'Waktu pengambilan surat diatur otomatis oleh sistem dan tidak dapat diubah manual.',
            'mulai_diproses_pada.prohibited' => 'Waktu mulai proses diatur otomatis oleh sistem dan tidak dapat diubah manual.',
            'selesai_pada.prohibited' => 'Waktu selesai diatur otomatis oleh sistem dan tidak dapat diubah manual.',
        ];
    }
}
