<?php

namespace App\Http\Requests;

use App\Models\Surat;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSuratDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $surat = $this->route('surat');

        return $surat instanceof Surat && $this->user()?->can('viewMine', $surat) === true;
    }

    public function rules(): array
    {
        return [
            'keterangan' => ['nullable', 'string', 'max:10000'],
            'editing_surat_id' => ['sometimes', 'integer'],
        ];
    }
}
