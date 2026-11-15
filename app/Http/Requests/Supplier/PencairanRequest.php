<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class PencairanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupplier() ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer'],
            'nilai' => ['required', 'numeric', 'min:1'],
            'cara_pencairan' => ['required', 'in:Tunai di bank sampah,Transfer e-wallet'],
        ];
    }
}
