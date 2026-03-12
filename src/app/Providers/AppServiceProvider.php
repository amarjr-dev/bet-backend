<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /*
     *
     * Rate Limite - Prática de segurança para proteger a API e evitar consumo abusivo dos recursos como...
     * Ataques de força bruta, scraping, excessivo, DoS, etc.
     *
     * Limita o número de requisições que um usuário autenticado ou IP pode fazer em um determinado período (ex: 60 requisições por minuto).
    **/
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
