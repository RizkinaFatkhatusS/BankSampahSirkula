<?php

namespace App\Policies;

use App\Models\Setoran;
use App\Models\User;

class SetoranPolicy
{
    /** Customer hanya setorannya sendiri; Supplier hanya setoran ke bank sampahnya. */
    public function view(User $user, Setoran $setoran): bool
    {
        if ($user->isCustomer()) {
            return $setoran->customer_id === $user->id;
        }

        return $user->isSupplier() && $user->bank_sampah_id === $setoran->bank_sampah_id;
    }

    public function batal(User $user, Setoran $setoran): bool
    {
        return $user->isCustomer()
            && $setoran->customer_id === $user->id
            && $setoran->bisaDibatalkan();
    }

    public function kelola(User $user, Setoran $setoran): bool
    {
        return $user->isSupplier() && $user->bank_sampah_id === $setoran->bank_sampah_id;
    }
}
