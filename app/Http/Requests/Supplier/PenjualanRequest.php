<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class PenjualanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupplier() ?? false;
    }

    public function rules(): array
    {
        return [
            'mitra_id' => ['required', 'integer'],
            'hasil_komunikasi' => ['required', 'in:disetujui,ditolak'],
            'tanggal' => ['required', 'date'],
            'status_pengiriman' => ['required', 'in:diproses,dikirim,diterima'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'bukti_pembayaran' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:'.\App\Services\Pengaturan::maksKb()],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_olahan_id' => ['required', 'integer', 'distinct'],
            'items.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'items.*.harga_jual' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mitra_id' => 'mitra',
            'items.*.produk_olahan_id' => 'produk',
            'items.*.jumlah' => 'jumlah',
            'items.*.harga_jual' => 'harga jual',
            'bukti_pembayaran' => 'bukti pembayaran',
        ];
    }
}
