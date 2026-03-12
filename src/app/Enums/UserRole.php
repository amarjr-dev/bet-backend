<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin   = 'admin';
    case Manager = 'manager';
    case Finance = 'finance';
    case User    = 'user';

    public function label(): string
    {
        return match($this) {
            UserRole::Admin   => 'Administrador',
            UserRole::Manager => 'Gerente',
            UserRole::Finance => 'Financeiro',
            UserRole::User    => 'Usuário',
        };
    }
}
