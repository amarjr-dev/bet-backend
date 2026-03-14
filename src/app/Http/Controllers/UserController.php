<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group Usuários
 *
 * Gerenciamento de usuários do sistema. Requer roles `admin` ou `manager`.
 * Managers não podem criar ou promover usuários com role `admin`.
 */
class UserController extends Controller
{
    /**
     * Listar Usuários
     *
     * Retorna a lista paginada de usuários (20 por página).
     *
     * @response 200 scenario="Sucesso" {
     *   "current_page": 1,
     *   "data": [
     *     {"id": 1, "name": "Mario Admin", "email": "admin@bet.com", "role": "admin", "created_at": "2026-01-01T00:00:00.000000Z"},
     *     {"id": 2, "name": "Joana Manager", "email": "manager@bet.com", "role": "manager", "created_at": "2026-01-01T00:00:00.000000Z"}
     *   ],
     *   "per_page": 20,
     *   "total": 2
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     */
    public function index(): JsonResponse
    {
        Log::info('Listagem de usuários acessada.');

        return response()->json(UserResource::collection(User::paginate(20)));
    }

    /**
     * Criar Usuário
     *
     * Cria um novo usuário no sistema. Roles válidos: `admin`, `manager`, `finance`.
     *
     * @response 201 scenario="Criado" {
     *   "id": 3,
     *   "name": "Carlos Finance",
     *   "email": "finance@bet.com",
     *   "role": "finance",
     *   "created_at": "2026-03-11T00:00:00.000000Z"
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 422 scenario="Validação" {"message": "O e-mail já está em uso.", "errors": {"email": ["Este e-mail já está em uso."], "role": ["Perfil inválido."]}}
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        Log::info('Usuário criado.', [
            'new_user_id' => $user->id,
            'role'        => $user->role,
        ]);

        return response()->json(new UserResource($user), 201);
    }

    /**
     * Exibir Usuário
     *
     * Retorna os detalhes de um usuário específico.
     *
     * @urlParam user integer required O ID do usuário. Example: 1
     *
     * @response 200 scenario="Sucesso" {
     *   "id": 1,
     *   "name": "Mario Admin",
     *   "email": "admin@bet.com",
     *   "role": "admin",
     *   "created_at": "2026-01-01T00:00:00.000000Z"
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\User] 99"}
     */
    public function show(User $user): JsonResponse
    {
        Log::info('Usuário consultado.', [
            'target_user_id' => $user->id,
        ]);

        return response()->json(new UserResource($user));
    }

    /**
     * Atualizar Usuário
     *
     * Atualiza dados de um usuário. Envie apenas os campos a alterar.
     *
     * @urlParam user integer required O ID do usuário. Example: 1
     *
     * @response 200 scenario="Sucesso" {
     *   "id": 1,
     *   "name": "Mario Admin Atualizado",
     *   "email": "admin@bet.com",
     *   "role": "admin",
     *   "created_at": "2026-01-01T00:00:00.000000Z"
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\User] 99"}
     * @response 422 scenario="Validação" {"message": "Este e-mail já está em uso.", "errors": {"email": ["Este e-mail já está em uso."]}}
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        Log::info('Usuário atualizado.', [
            'target_user_id' => $user->id,
            'fields'         => array_keys($request->validated()),
        ]);

        return response()->json(new UserResource($user));
    }

    /**
     * Remover Usuário
     *
     * Remove (soft-delete) um usuário. Não é possível excluir o próprio usuário autenticado.
     *
     * @urlParam user integer required O ID do usuário. Example: 2
     *
     * @response 200 scenario="Sucesso" {"message": "Usuário removido com sucesso."}
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\User] 99"}
     * @response 422 scenario="Auto-exclusão negada" {"message": "Não é possível excluir seu próprio usuário."}
     */
    public function destroy(User $user): JsonResponse
    {
        // Impede auto-exclusão
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Não é possível excluir seu próprio usuário.'], 422);
        }

        $user->delete();

        Log::info('Usuário removido.', [
            'deleted_user_id' => $user->id,
        ]);

        return response()->json(['message' => 'Usuário removido com sucesso.']);
    }
}
