<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedRoles = array_column(UserRole::cases(), 'value');

        // MANAGER não pode criar usuários ADMIN
        if ($this->user()?->role === UserRole::Manager) {
            $allowedRoles = array_filter($allowedRoles, fn($r) => $r !== UserRole::Admin->value);
        }

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', Rule::in($allowedRoles)],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name'     => ['description' => 'Nome completo do usuário.', 'example' => 'Carlos Finance'],
            'email'    => ['description' => 'E-mail único do usuário.', 'example' => 'finance@bet.com'],
            'password' => ['description' => 'Senha com no mínimo 8 caracteres.', 'example' => 'senha@123'],
            'role'     => ['description' => 'Role do usuário. Valores aceitos: `admin`, `manager`, `finance`.', 'example' => 'finance'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'O nome é obrigatório.',
            'email.required'    => 'O e-mail é obrigatório.',
            'email.unique'      => 'Este e-mail já está em uso.',
            'password.required' => 'A senha é obrigatória.',
            'password.min'      => 'A senha deve ter no mínimo 8 caracteres.',
            'role.required'     => 'O perfil é obrigatório.',
            'role.in'           => 'Perfil inválido.',
        ];
    }
}
