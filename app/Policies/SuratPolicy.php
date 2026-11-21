<?php

namespace App\Policies;

use App\Models\Surat;
use App\Models\User;

class SuratPolicy
{
    public function delete(User $user, Surat $surat): bool
    {
        return true;
    }

    public function viewMine(User $user, Surat $surat): bool
    {
        return $surat->pegawai_id === $user->id;
    }

    public function updateStatus(User $user, Surat $surat): bool
    {
        return $this->viewMine($user, $surat);
    }

    public function cancelAssignment(User $user, Surat $surat): bool
    {
        return $this->viewMine($user, $surat)
            && $surat->status !== Surat::STATUS_SELESAI;
    }
}
