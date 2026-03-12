<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name'   => ['description' => 'Nome do produto.', 'example' => 'Camiseta Premium'],
            'amount' => ['description' => 'Preço do produto em centavos. Ex: 9999 = R$ 99,99.', 'example' => 9999],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'O nome do produto é obrigatório.',
            'amount.required' => 'O valor do produto é obrigatório.',
            'amount.integer'  => 'O valor deve ser um número inteiro (em centavos).',
            'amount.min'      => 'O valor mínimo é 1 centavo.',
        ];
    }
}
