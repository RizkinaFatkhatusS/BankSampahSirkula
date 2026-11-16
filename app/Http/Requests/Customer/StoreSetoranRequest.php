<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreSetoranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    public function rules(): array
    {
        return [
            'bank_sampah_id' => ['required', 'exists:bank_sampah,id'],
            'jenis_sampah_ids' => ['required', 'array', 'min:1'],
            'jenis_sampah_ids.*' => ['integer', 'distinct'],
            // Foto wajib (PRD FR-07): jpg/jpeg/png, maksimal 2 MB
            'foto_bukti' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:'.\App\Services\Pengaturan::maksKb()],
            'metode_penyerahan' => ['required', 'in:dijemput_kurir,antar_sendiri'],
            'alamat_jemput' => ['required_if:metode_penyerahan,dijemput_kurir', 'nullable', 'string', 'max:500'],
            'jadwal_jemput' => ['required_if:metode_penyerahan,dijemput_kurir', 'nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'jenis_sampah_ids' => 'jenis sampah',
            'foto_bukti' => 'foto sampah',
            'metode_penyerahan' => 'metode penyerahan',
            'alamat_jemput' => 'alamat penjemputan',
            'jadwal_jemput' => 'jadwal penjemputan',
        ];
    }

    public function messages(): array
    {
        return [
            'foto_bukti.required' => 'Foto sampah wajib diunggah sebagai bukti.',
            'jenis_sampah_ids.required' => 'Pilih minimal satu jenis sampah.',
            'jenis_sampah_ids.min' => 'Pilih minimal satu jenis sampah.',
        ];
    }
}
