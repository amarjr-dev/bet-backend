<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        $allowedRoles = array_column(UserRole::cases(), 'value');

        // MANAGER não pode promover usuário a ADMIN
        if ($this->user()?->role === UserRole::Manager) {
            $allowedRoles = array_filter($allowedRoles, fn($r) => $r !== UserRole::Admin->value);
        }

        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'string', 'min:8'],
            'role'     => ['sometimes', Rule::in($allowedRoles)],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name'     => ['description' => 'Novo nome do usuário (opcional).', 'example' => 'Mario Admin Atualizado'],
            'email'    => ['description' => 'Novo e-mail único (opcional).', 'example' => 'novo@bet.com'],
            'password' => ['description' => 'Nova senha com no mínimo 8 caracteres (opcional).', 'example' => 'novaSenha@456'],
            'role'     => ['description' => 'Novo role do usuário (opcional). Valores aceitos: `admin`, `manager`, `finance`.', 'example' => 'manager'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'   => 'Este e-mail já está em uso.',
            'password.min'   => 'A senha deve ter no mínimo 8 caracteres.',
            'role.in'        => 'Perfil inválido.',
        ];
    }
}
