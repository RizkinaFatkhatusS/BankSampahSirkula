<?php

namespace App\Console\Commands;

use App\Services\PengajuanService;
use Illuminate\Console\Command;

class PengajuanPengingat extends Command
{
    protected $signature = 'sirkula:pengajuan-pengingat';

    protected $description = 'Ingatkan Admin tentang pengajuan yang belum diproses melebihi target hari kerja';

    public function handle(PengajuanService $service): int
    {
        $this->info($service->ingatkanAntrean().' pengingat dikirim.');

        return self::SUCCESS;
    }
}
