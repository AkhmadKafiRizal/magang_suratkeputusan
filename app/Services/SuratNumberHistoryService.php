<?php

namespace App\Services;

use App\Models\AktivitasSurat;
use App\Models\Surat;
use Illuminate\Support\Collection;

class SuratNumberHistoryService
{
    /** @return Collection<int, AktivitasSurat> */
    public function for(Surat $surat, ?int $limit = null): Collection
    {
        $query = AktivitasSurat::query()
            ->whereIn('surat_id', $this->suratIdsFor($surat))
            ->with([
                'actor:id,name,profile_photo_path',
                'surat:id,nomor_surat,deleted_at',
            ])
            ->latest('created_at')
            ->latest('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /** @return Collection<int, int> */
    public function suratIdsFor(Surat $surat): Collection
    {
        return Surat::query()
            ->withTrashed()
            ->whereRaw('LOWER(TRIM(nomor_surat)) = ?', [mb_strtolower(trim($surat->nomor_surat))])
            ->pluck('id');
    }
}
