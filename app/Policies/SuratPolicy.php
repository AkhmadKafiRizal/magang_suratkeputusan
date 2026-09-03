<?php

namespace App\Policies;

use App\Models\Surat;
use App\Models\User;

class SuratPolicy
{
    public function viewMine(User $user, Surat $surat): bool
    {
        return $user->role === User::ROLE_PEGAWAI
            && $surat->pegawai_id === $user->id;
    }

    public function updateStatus(User $user, Surat $surat): bool
    {
        return $this->viewMine($user, $surat);
    }
}
