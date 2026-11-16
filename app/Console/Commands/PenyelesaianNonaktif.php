<?php

namespace App\Console\Commands;

use App\Services\PenghentianService;
use Illuminate\Console\Command;

class PenyelesaianNonaktif extends Command
{
    protected $signature = 'sirkula:penyelesaian-nonaktif';

    protected $description = 'Alihkan akun Dalam penyelesaian ke Nonaktif bila masa berakhir dan syarat terpenuhi';

    public function handle(PenghentianService $service): int
    {
        $h = $service->selesaikanOtomatis();
        $this->info("{$h['nonaktif']} akun dinonaktifkan, {$h['diingatkan']} Admin diingatkan.");

        return self::SUCCESS;
    }
}
