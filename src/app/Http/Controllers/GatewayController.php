<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGatewayPriorityRequest;
use App\Http\Resources\GatewayResource;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;

/**
 * @group Gateways
 *
 * Gerenciamento de gateways de pagamento. Requer role `admin`.
 */
class GatewayController extends Controller
{
    /**
     * Listar Gateways
     *
     * Retorna todos os gateways ordenados por prioridade.
     *
     * @response 200 scenario="Sucesso" [
     *   {"id": 1, "name": "Gateway Principal", "driver": "gateway1", "is_active": true, "priority": 1},
     *   {"id": 2, "name": "Gateway Backup", "driver": "gateway2", "is_active": true, "priority": 2}
     * ]
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     */
    public function index(): JsonResponse
    {
        $gateways = Gateway::orderBy('priority')->get();

        return response()->json(GatewayResource::collection($gateways));
    }

    /**
     * Ativar/Desativar Gateway
     *
     * Alterna o status `is_active` do gateway entre `true` e `false`.
     *
     * @urlParam gateway integer required O ID do gateway. Example: 1
     *
     * @response 200 scenario="Sucesso" {
     *   "id": 1,
     *   "name": "Gateway Principal",
     *   "driver": "gateway1",
     *   "is_active": false,
     *   "priority": 1
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\Gateway] 99"}
     */
    public function toggle(Gateway $gateway): JsonResponse
    {
        $gateway->update(['is_active' => ! $gateway->is_active]);

        return response()->json(new GatewayResource($gateway));
    }

    /**
     * Atualizar Prioridade do Gateway
     *
     * Define a prioridade de uso do gateway. Prioridade 1 = mais prioritário.
     *
     * @urlParam gateway integer required O ID do gateway. Example: 1
     *
     * @response 200 scenario="Sucesso" {
     *   "id": 1,
     *   "name": "Gateway Principal",
     *   "driver": "gateway1",
     *   "is_active": true,
     *   "priority": 2
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\Gateway] 99"}
     * @response 422 scenario="Validação" {"message": "A prioridade é obrigatória.", "errors": {"priority": ["A prioridade é obrigatória."]}}
     */
    public function updatePriority(UpdateGatewayPriorityRequest $request, Gateway $gateway): JsonResponse
    {
        $gateway->update(['priority' => $request->input('priority')]);

        return response()->json(new GatewayResource($gateway));
    }
}
