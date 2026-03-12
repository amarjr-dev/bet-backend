<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * @group Autenticação
 *
 * Endpoints para autenticação de usuários via Bearer Token (Sanctum).
 */
class AuthController extends Controller
{
    /**
     * Login
     *
     * Autentica o usuário e retorna um Bearer Token para uso nas demais requisições.
     *
     * @unauthenticated
     *
     * @response 200 scenario="Sucesso" {
     *   "token": "1|abc123def456...",
     *   "user": {
     *     "id": 1,
     *     "name": "Mario Admin",
     *     "email": "admin@bet.com",
     *     "role": "admin",
     *     "created_at": "2026-01-01T00:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Credenciais inválidas" {
     *   "message": "Credenciais inválidas."
     * }
     * @response 422 scenario="Validação" {
     *   "message": "O e-mail é obrigatório.",
     *   "errors": {
     *     "email": ["O e-mail é obrigatório."],
     *     "password": ["A senha é obrigatória."]
     *   }
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        // Revoga tokens antigos e emite um novo
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    /**
     * Logout
     *
     * Revoga o Bearer Token atual. O token deixa de ser válido imediatamente.
     *
     * @response 200 scenario="Sucesso" {
     *   "message": "Sessão encerrada."
     * }
     * @response 401 scenario="Não autenticado" {
     *   "message": "Unauthenticated."
     * }
     */
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }
}
