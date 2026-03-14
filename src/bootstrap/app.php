<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\LogUserContext;
use Illuminate\Auth\AuthenticationException;
use Viu\ViuLaravel\Middleware\ViuCorrelationMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
        ]);
        $middleware->appendToGroup('api', ViuCorrelationMiddleware::class);
        $middleware->appendToGroup('api', LogUserContext::class);
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (AuthenticationException $e, Request $request): JsonResponse {
            return response()->json(['message' => 'Não autenticado.'], 401);
        });

        $exceptions->render(function (ValidationException $e, Request $request): JsonResponse {
            return response()->json([
                'message' => 'Dados inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request): JsonResponse {
            return response()->json(['message' => 'Recurso não encontrado.'], 404);
        });

        $exceptions->render(function (HttpException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage() ?: 'Erro na requisição.'], $e->getStatusCode());
        });

    })->create();
