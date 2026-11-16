<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Identitas akun dikunci: email Supplier/Admin/Super Admin dan nama penanggung jawab Supplier
     * hanya berubah setelah verifikasi ulang oleh Admin (FR-39) atau oleh Super Admin (FR-47).
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if (! $user->isCustomer()) {
            $this->merge(['email' => $user->email]);
        }
        if ($user->isSupplier()) {
            $this->merge(['nama' => $user->nama]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nomor_hp' => ['required', 'string', 'max:20'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
