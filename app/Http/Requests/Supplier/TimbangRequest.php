<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class TimbangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupplier() ?? false;
    }

    public function rules(): array
    {
        return [
            'berat' => ['required', 'array', 'min:1'],
            'berat.*' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ];
    }

    public function attributes(): array
    {
        return ['berat.*' => 'berat aktual'];
    }
}
