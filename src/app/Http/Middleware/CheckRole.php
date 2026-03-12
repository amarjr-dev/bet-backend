<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckRole
{
    /**
     * Verifica se o usuário autenticado possui um dos roles informados.
     *
     * Uso: ->middleware('role:admin,manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new HttpException(401, 'Não autenticado.');
        }

        $allowed = array_map(
            fn(string $role) => UserRole::from($role),
            $roles
        );

        if (! $user->hasRole(...$allowed)) {
            throw new HttpException(403, 'Acesso negado. Permissão insuficiente.');
        }

        return $next($request);
    }
}
