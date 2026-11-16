<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

/** Dipakai bersama oleh Customer (/customer/notifikasi) dan Supplier (/supplier/notifikasi). */
class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->user()->notifications()->limit(100)->get()->map(fn ($n) => [
            'id' => $n->id,
            'judul' => $n->data['judul'] ?? '',
            'pesan' => $n->data['pesan'] ?? '',
            'tipe' => $n->data['tipe'] ?? 'info',
            'setoran_id' => $n->data['setoran_id'] ?? null,
            'url' => $n->data['url'] ?? null,
            'dibaca' => $n->read_at !== null,
            'waktu' => $n->created_at->diffForHumans(),
        ]);

        return Inertia::render('Notifikasi', ['notifikasi' => $items, 'role' => $request->user()->role]);
    }

    public function baca(Request $request, string $id)
    {
        $request->user()->notifications()->where('id', $id)->first()?->markAsRead();

        return back();
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
