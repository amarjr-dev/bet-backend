<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogUserContext
{
    /**
     * Injeta o usuário autenticado no contexto global de log da requisição.
     * Aplica-se a todos os Log::* subsequentes via Log::withContext().
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            Log::withContext([
                'user' => [
                    'id'    => $user->id,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ]);
        }

        return $next($request);
    }
}
