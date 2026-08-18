<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIssuanceDataRequest extends FormRequest
{
    /**
     * O gate de posse do recurso já é feito por `EnsureIssuanceAccessTokenIsValid`
     * antes deste Request rodar (RF-17).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras condicionais por `?tipo=` (RF-02): PF (padrão, `tipo` ausente ou
     * diferente de `pj`) exige os campos de titular/endereço; PJ (`?tipo=pj`)
     * exige adicionalmente os campos de responsável, mantendo os campos de
     * endereço como dados da empresa.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $rules = [
            'holder_name' => ['required', 'string', 'max:180'],
            'document' => ['required', 'digits:11'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:20'],
            'postal_code' => ['required', 'digits:8'],
            'street' => ['required', 'string', 'max:180'],
            'number' => ['required', 'string', 'max:20'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
        ];

        if ($this->query('tipo') === 'pj') {
            $rules['responsible_name'] = ['required', 'string', 'max:180'];
            $rules['responsible_document'] = ['required', 'digits:11'];
            $rules['responsible_email'] = ['required', 'email', 'max:180'];
            $rules['responsible_phone'] = ['required', 'string', 'max:20'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'email' => 'O campo :attribute deve ser um e-mail válido.',
            'digits' => 'O campo :attribute deve conter exatamente :digits dígitos.',
            'size' => 'O campo :attribute deve conter exatamente :size caracteres.',
        ];
    }
}
