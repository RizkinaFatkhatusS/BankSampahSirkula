<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\BankSampah;

abstract class BaseController extends Controller
{
    /** Semua query Supplier dibatasi ke bank sampahnya sendiri (PRD 5.3). */
    protected function bankId(): int
    {
        $id = auth()->user()->bank_sampah_id;
        abort_unless($id, 403, 'Akun Supplier belum terhubung ke bank sampah.');

        return (int) $id;
    }

    protected function bank(): BankSampah
    {
        return BankSampah::findOrFail($this->bankId());
    }
}
