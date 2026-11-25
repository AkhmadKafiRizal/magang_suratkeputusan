<?php

namespace App\Http\Requests;

use App\Models\Surat;
use Illuminate\Validation\Rule;

class BulkUpdateSuratStatusRequest extends BulkSuratRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['required', Rule::in([
                Surat::STATUS_SEDANG_DIPROSES,
                Surat::STATUS_SELESAI,
            ])],
        ]);
    }
}
