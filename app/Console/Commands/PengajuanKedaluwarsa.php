<?php

namespace App\Console\Commands;

use App\Services\PengajuanService;
use Illuminate\Console\Command;

class PengajuanKedaluwarsa extends Command
{
    protected $signature = 'sirkula:pengajuan-kedaluwarsa';

    protected $description = 'Pengajuan Supplier berstatus Perlu revisi yang tidak dikirim ulang menjadi Kedaluwarsa';

    public function handle(PengajuanService $service): int
    {
        $this->info($service->kedaluwarsakan().' pengajuan kedaluwarsa.');

        return self::SUCCESS;
    }
}
