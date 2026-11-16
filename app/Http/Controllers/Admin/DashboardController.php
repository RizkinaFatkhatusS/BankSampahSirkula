<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LaporanPlatformService;
use Inertia\Inertia;

/** Dashboard platform: hanya agregat, tanpa data pribadi atau saldo Customer (FR-43). */
class DashboardController extends Controller
{
    public function index(LaporanPlatformService $service)
    {
        return Inertia::render('Admin/Dashboard', $service->dashboard());
    }
}
