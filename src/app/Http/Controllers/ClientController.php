<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

/**
 * @group Clientes
 *
 * Consulta de clientes cadastrados automaticamente ao realizar compras.
 * Requer roles `admin`, `manager` ou `finance`.
 */
class ClientController extends Controller
{
    /**
     * Listar Clientes
     *
     * Retorna a lista paginada de clientes (20 por página).
     *
     * @response 200 scenario="Sucesso" {
     *   "current_page": 1,
     *   "data": [
     *     {"id": 1, "name": "João da Silva", "email": "joao@email.com", "transactions": [], "created_at": "2026-01-01T00:00:00.000000Z"}
     *   ],
     *   "per_page": 20,
     *   "total": 1
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     */
    public function index(): JsonResponse
    {
        $clients = Client::paginate(20);

        return response()->json($clients->through(fn($c) => new ClientResource($c)));
    }

    /**
     * Exibir Cliente
     *
     * Retorna os detalhes de um cliente com seu histórico completo de transações.
     *
     * @urlParam client integer required O ID do cliente. Example: 1
     *
     * @response 200 scenario="Sucesso" {
     *   "id": 1,
     *   "name": "João da Silva",
     *   "email": "joao@email.com",
     *   "transactions": [
     *     {
     *       "id": 1,
     *       "status": "approved",
     *       "amount": 19998,
     *       "card_last_numbers": "1234",
     *       "external_id": "ext_abc123",
     *       "gateway": {"id": 1, "name": "Gateway Principal"},
     *       "products": [
     *         {"product_id": 1, "product_name": "Camiseta Premium", "quantity": 2, "unit_price": 9999, "subtotal": 19998}
     *       ],
     *       "created_at": "2026-03-11T00:00:00.000000Z"
     *     }
     *   ],
     *   "created_at": "2026-01-01T00:00:00.000000Z"
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\Client] 99"}
     */
    public function show(Client $client): JsonResponse
    {
        $client->load(['transactions' => function ($query) {
            $query->with('gateway', 'products.product')->latest();
        }]);

        return response()->json(new ClientResource($client));
    }
}
