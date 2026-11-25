<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'surat_ids' => ['required', 'array', 'min:1', 'max:50'],
            'surat_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'surat_ids.required' => 'Pilih setidaknya satu surat.',
            'surat_ids.max' => 'Maksimal 50 surat dapat diproses dalam satu tindakan.',
        ];
    }
}
