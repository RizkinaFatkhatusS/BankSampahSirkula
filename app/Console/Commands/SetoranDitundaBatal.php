<?php

namespace App\Console\Commands;

use App\Services\PenghentianService;
use Illuminate\Console\Command;

class SetoranDitundaBatal extends Command
{
    protected $signature = 'sirkula:setoran-ditunda-batal';

    protected $description = 'Batalkan otomatis setoran Ditunda yang melewati batas hari';

    public function handle(PenghentianService $service): int
    {
        $this->info($service->batalkanDitundaLewatBatas().' setoran dibatalkan.');

        return self::SUCCESS;
    }
}
