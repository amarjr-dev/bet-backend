<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client'             => ['required', 'array'],
            'client.name'        => ['required', 'string', 'max:255'],
            'client.email'       => ['required', 'string', 'email', 'max:255'],
            'client.cardNumber'  => ['required', 'string', 'size:16', 'regex:/^\d{16}$/'],
            'client.cvv'         => ['required', 'string', 'size:3', 'regex:/^\d{3}$/'],
            'products'           => ['required', 'array', 'min:1'],
            'products.*.id'      => ['required', 'integer', 'exists:products,id,deleted_at,NULL'],
            'products.*.quantity'=> ['required', 'integer', 'min:1'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'client.name'       => ['description' => 'Nome igual do cartão.', 'example' => 'João da Silva'],
            'client.email'      => ['description' => 'E-mail usado no cadastro.', 'example' => 'joao@email.com'],
            'client.cardNumber' => ['description' => 'Número do cartão de crédito (16 dígitos, apenas números).', 'example' => '4444333311112222'],
            'client.cvv'        => ['description' => 'Código de segurança do cartão (3 dígitos).', 'example' => '123'],
            'products.*.id'     => ['description' => 'ID do produto.', 'example' => 1],
            'products.*.quantity' => ['description' => 'Quantidade do produto.', 'example' => 2],
        ];
    }

    public function messages(): array
    {
        return [
            'client.required'            => 'Os dados do cliente são obrigatórios.',
            'client.name.required'       => 'O nome do cliente é obrigatório.',
            'client.email.required'      => 'O e-mail do cliente é obrigatório.',
            'client.email.email'         => 'O e-mail do cliente deve ser válido.',
            'client.cardNumber.required' => 'O número do cartão é obrigatório.',
            'client.cardNumber.size'     => 'O número do cartão deve ter exatamente 16 dígitos.',
            'client.cardNumber.regex'    => 'O número do cartão deve conter apenas dígitos.',
            'client.cvv.required'        => 'O CVV é obrigatório.',
            'client.cvv.size'             => 'O CVV deve ter exatamente 3 dígitos.',
            'client.cvv.regex'           => 'O CVV deve conter apenas dígitos.',
            'products.required'          => 'Ao menos um produto deve ser informado.',
            'products.*.id.required'     => 'O ID do produto é obrigatório.',
            'products.*.id.exists'       => 'Produto não encontrado.',
            'products.*.quantity.required' => 'A quantidade do produto é obrigatória.',
            'products.*.quantity.min'    => 'A quantidade mínima por produto é 1.',
        ];
    }
}
