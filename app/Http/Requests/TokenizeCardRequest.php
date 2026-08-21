<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TokenizeCardRequest extends FormRequest
{
    /**
     * Rota pública, sem gate de posse — mesma acessibilidade de `checkout` (CT-01).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'holder' => ['required', 'string', 'max:180'],
            'cardNumber' => ['required', 'string', 'regex:/^[0-9]{12,19}$/'],
            'expirationDate' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/[0-9]{4}$/'],
            'securityCode' => ['required', 'string', 'regex:/^[0-9]{3,4}$/'],
        ];
    }
}
