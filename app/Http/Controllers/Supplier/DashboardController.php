<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Supplier/Dashboard', [
            'user' => auth()->user(),
            'message' => 'Selamat datang di Dashboard Petugas/Supplier Bank Sampah'
        ]);
    }
}