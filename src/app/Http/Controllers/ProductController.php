<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group Produtos
 *
 * Gerenciamento do catálogo de produtos disponíveis para aposta.
 * Leitura: todos autenticados. Criação/Edição: roles `admin`, `manager`, `finance`. Exclusão: roles `admin`, `manager`.
 */
class ProductController extends Controller
{
    /**
     * Listar Produtos
     *
     * Retorna a lista paginada de produtos ativos (20 por página).
     *
     * @response 200 scenario="Sucesso" {
     *   "current_page": 1,
     *   "data": [
     *     {"id": 1, "name": "Camiseta Premium", "amount": 9999, "amount_brl": "R$ 99,99", "created_at": "2026-01-01T00:00:00.000000Z"},
     *     {"id": 2, "name": "Bone Exclusivo", "amount": 4990, "amount_brl": "R$ 49,90", "created_at": "2026-01-01T00:00:00.000000Z"}
     *   ],
     *   "per_page": 20,
     *   "total": 2
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     */
    public function index(): JsonResponse
    {
        return response()->json(ProductResource::collection(Product::paginate(20)));
    }

    /**
     * Criar Produto
     *
     * Cria um novo produto no catálogo. O `amount` deve ser informado em centavos.
     *
     * @response 201 scenario="Criado" {
     *   "id": 3,
     *   "name": "Camiseta Premium",
     *   "amount": 9999,
     *   "amount_brl": "R$ 99,99",
     *   "created_at": "2026-03-11T00:00:00.000000Z"
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 422 scenario="Validação" {"message": "O nome do produto é obrigatório.", "errors": {"name": ["O nome do produto é obrigatório."], "amount": ["O valor do produto é obrigatório."]}}
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        Log::info('Produto criado.', [
            'product_id' => $product->id,
            'name'       => $product->name,
        ]);

        return response()->json(new ProductResource($product), 201);
    }

    /**
     * Exibir Produto
     *
     * Retorna os detalhes de um produto específico.
     *
     * @urlParam product integer required O ID do produto. Example: 1
     *
     * @response 200 scenario="Sucesso" {
     *   "id": 1,
     *   "name": "Camiseta Premium",
     *   "amount": 9999,
     *   "amount_brl": "R$ 99,99",
     *   "created_at": "2026-01-01T00:00:00.000000Z"
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\Product] 99"}
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json(new ProductResource($product));
    }

    /**
     * Atualizar Produto
     *
     * Atualiza um produto existente. Envie apenas os campos que deseja alterar.
     *
     * @urlParam product integer required O ID do produto. Example: 1
     *
     * @response 200 scenario="Sucesso" {
     *   "id": 1,
     *   "name": "Camiseta Premium Atualizada",
     *   "amount": 11990,
     *   "amount_brl": "R$ 119,90",
     *   "created_at": "2026-01-01T00:00:00.000000Z"
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\Product] 99"}
     * @response 422 scenario="Validação" {"message": "O valor deve ser um número inteiro (em centavos).", "errors": {"amount": ["O valor deve ser um número inteiro (em centavos)."]}}
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        Log::info('Produto atualizado.', [
            'product_id' => $product->id,
            'fields'     => array_keys($request->validated()),
        ]);

        return response()->json(new ProductResource($product));
    }

    /**
     * Remover Produto
     *
     * Remove (soft-delete) um produto do catálogo.
     *
     * @urlParam product integer required O ID do produto. Example: 1
     *
     * @response 200 scenario="Sucesso" {"message": "Produto removido com sucesso."}
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\Product] 99"}
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        Log::info('Produto removido.', [
            'product_id' => $product->id,
            'name'       => $product->name,
        ]);

        return response()->json(['message' => 'Produto removido com sucesso.']);
    }
}
