<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Tugas terjadwal SIRKULA (PRD 5.3). Jalankan "php artisan schedule:run" tiap menit
 * (cron di Linux, atau Task Scheduler di Windows).
 */
Schedule::command('sirkula:pengajuan-kedaluwarsa')->dailyAt('01:00');
Schedule::command('sirkula:pengajuan-pengingat')->dailyAt('08:00');
Schedule::command('sirkula:setoran-ditunda-batal')->dailyAt('01:30');
Schedule::command('sirkula:penyelesaian-nonaktif')->dailyAt('02:00');
