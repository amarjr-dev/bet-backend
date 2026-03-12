<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name'   => ['description' => 'Novo nome do produto (opcional).', 'example' => 'Camiseta Premium Atualizada'],
            'amount' => ['description' => 'Novo preço em centavos (opcional). Ex: 11990 = R$ 119,90.', 'example' => 11990],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.integer' => 'O valor deve ser um número inteiro (em centavos).',
            'amount.min'     => 'O valor mínimo é 1 centavo.',
        ];
    }
}
