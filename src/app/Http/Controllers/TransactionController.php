<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Client;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @group Transações
 *
 * Consulta e gerenciamento de transações de pagamento. Requer roles `admin` ou `finance`.
 * O endpoint de compra (`POST /api/purchases`) é público e está documentado no grupo **Compras**.
 */
class TransactionController extends Controller
{
    public function __construct(private PaymentGatewayService $gatewayService) {}

    /**
     * Realizar Compra
     *
     * Endpoint público para processar um pagamento. O cliente é criado ou recuperado pelo e-mail.
     * O valor total é calculado no servidor com base nos preços dos produtos.
     * A cobrança é processada automaticamente pelo gateway ativo com maior prioridade.
     *
     * Obs: Para simular erro de dados inválidos do cartão:
     *      - No Gateway Primário (1): use o cvv 100 ou 200.
     *      - No Gateway Primário (1): use o cvv 200 ou 300.
     *
     * @group Compras
     * @unauthenticated
     *
     * @response 201 scenario="Aprovado" {
     *   "id": 1,
     *   "status": "approved",
     *   "amount": 19998,
     *   "card_last_numbers": "4242",
     *   "external_id": "ext_abc123",
     *   "gateway": {"id": 1, "name": "Gateway Principal", "driver": "gateway1"},
     *   "client": {"id": 1, "name": "João da Silva", "email": "joao@email.com"},
     *   "products": [
     *     {"product_id": 1, "product_name": "Camiseta Premium", "quantity": 2, "unit_price": 9999, "subtotal": 19998}
     *   ],
     *   "created_at": "2026-03-11T00:00:00.000000Z"
     * }
     * @response 422 scenario="Validação" {
     *   "message": "O número do cartão deve ter exatamente 16 dígitos.",
     *   "errors": {
     *     "client.cardNumber": ["O número do cartão deve ter exatamente 16 dígitos."],
     *     "products": ["Ao menos um produto deve ser informado."]
     *   }
     * }
     * @response 503 scenario="Nenhum gateway disponível" {"message": "Nenhum gateway de pagamento disponível."}
     */
    public function store(PurchaseRequest $request): JsonResponse
    {
        $clientData  = $request->input('client');
        $productsData = $request->input('products');

        // Carrega os produtos e calcula o valor total no backend.
        $productIds = array_column($productsData, 'id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $amount = 0;
        foreach ($productsData as $item) {
            $amount += $products[$item['id']]->amount * $item['quantity'];
        }

        $payload = [
            'amount'     => $amount,
            'name'       => $clientData['name'],
            'email'      => $clientData['email'],
            'cardNumber' => $clientData['cardNumber'],
            'cvv'        => $clientData['cvv'],
        ];

        Log::info('Compra iniciada.', [
            'client_email' => $clientData['email'],
            'amount'       => $amount,
            'product_ids'  => $productIds,
        ]);

        $result = $this->gatewayService->charge($payload);

        $transaction = DB::transaction(function () use ($clientData, $productsData, $products, $amount, $result) {
            $client = Client::firstOrCreate(
                ['email' => $clientData['email']],
                ['name'  => $clientData['name']]
            );

            $transaction = Transaction::create([
                'client_id'         => $client->id,
                'gateway_id'        => $result['gateway']->id,
                'external_id'       => $result['external_id'],
                'status'            => 'approved',
                'amount'            => $amount,
                'card_last_numbers' => substr($clientData['cardNumber'], -4),
            ]);

            foreach ($productsData as $item) {
                $transaction->products()->create([
                    'product_id' => $item['id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $products[$item['id']]->amount,
                ]);
            }

            return $transaction;
        });

        Log::info('Compra concluída com sucesso.', [
            'transaction_id' => $transaction->id,
            'external_id'    => $result['external_id'],
            'gateway'        => $result['gateway']->name,
            'amount'         => $amount,
            'client_email'   => $clientData['email'],
        ]);

        return response()->json(
            new TransactionResource($transaction->load('client', 'gateway', 'products.product')),
            201
        );
    }

    /**
     * Listar Transações
     *
     * Retorna a lista paginada de transações (20 por página), da mais recente para a mais antiga.
     *
     * @response 200 scenario="Sucesso" {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "status": "approved",
     *       "amount": 19998,
     *       "card_last_numbers": "4242",
     *       "external_id": "ext_abc123",
     *       "gateway": {"id": 1, "name": "Gateway Principal", "driver": "gateway1"},
     *       "client": {"id": 1, "name": "João da Silva", "email": "joao@email.com"},
     *       "products": [
     *         {"product_id": 1, "product_name": "Camiseta Premium", "quantity": 2, "unit_price": 9999, "subtotal": 19998}
     *       ],
     *       "created_at": "2026-03-11T00:00:00.000000Z"
     *     }
     *   ],
     *   "per_page": 20,
     *   "total": 1
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     */
    public function index(Request $request): JsonResponse
    {
        Log::info('Listagem de transações acessada.');

        $transactions = Transaction::with('client', 'gateway', 'products.product')
            ->latest()
            ->paginate(20);

        return response()->json($transactions->through(fn($t) => new TransactionResource($t)));
    }

    /**
     * Exibir Transação
     *
     * Retorna os detalhes completos de uma transação, incluindo cliente, gateway e produtos.
     *
     * @urlParam transaction integer required O ID da transação. Example: 1
     *
     * @response 200 scenario="Sucesso" {
     *   "id": 1,
     *   "status": "approved",
     *   "amount": 19998,
     *   "card_last_numbers": "4242",
     *   "external_id": "ext_abc123",
     *   "gateway": {"id": 1, "name": "Gateway Principal", "driver": "gateway1"},
     *   "client": {"id": 1, "name": "João da Silva", "email": "joao@email.com"},
     *   "products": [
     *     {"product_id": 1, "product_name": "Camiseta Premium", "quantity": 2, "unit_price": 9999, "subtotal": 19998}
     *   ],
     *   "created_at": "2026-03-11T00:00:00.000000Z"
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\Transaction] 99"}
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load('client', 'gateway', 'products.product');

        Log::info('Transação consultada.', [
            'transaction_id' => $transaction->id,
        ]);

        return response()->json(new TransactionResource($transaction));
    }

    /**
     * Reembolsar Transação
     *
     * Solicita o reembolso de uma transação aprovada. Apenas transações com status `approved` podem ser reembolsadas.
     * O reembolso é processado no gateway de origem e o status é atualizado para `refunded`.
     *
     * @urlParam transaction integer required O ID da transação. Example: 1
     *
     * @response 200 scenario="Reembolsado" {
     *   "id": 1,
     *   "status": "refunded",
     *   "amount": 19998,
     *   "card_last_numbers": "4242",
     *   "external_id": "ext_abc123",
     *   "gateway": {"id": 1, "name": "Gateway Principal", "driver": "gateway1"},
     *   "client": {"id": 1, "name": "João da Silva", "email": "joao@email.com"},
     *   "products": [
     *     {"product_id": 1, "product_name": "Camiseta Premium", "quantity": 2, "unit_price": 9999, "subtotal": 19998}
     *   ],
     *   "created_at": "2026-03-11T00:00:00.000000Z"
     * }
     * @response 401 scenario="Não autenticado" {"message": "Unauthenticated."}
     * @response 403 scenario="Sem permissão" {"message": "This action is unauthorized."}
     * @response 404 scenario="Não encontrado" {"message": "No query results for model [App\\Models\\Transaction] 99"}
     * @response 422 scenario="Não reembolsável" {"message": "Apenas transações aprovadas podem ser reembolsadas."}
     */
    public function refund(Transaction $transaction): JsonResponse
    {
        if ($transaction->status->value !== 'approved') {
            return response()->json(['message' => 'Apenas transações aprovadas podem ser reembolsadas.'], 422);
        }

        Log::info('Reembolso solicitado.', [
            'transaction_id' => $transaction->id,
            'requested_by'   => auth()->id(),
        ]);

        $this->gatewayService->refund($transaction);

        $transaction->update(['status' => 'refunded']);

        Log::info('Reembolso concluído com sucesso.', [
            'transaction_id' => $transaction->id,
            'gateway'        => $transaction->gateway->name,
        ]);

        return response()->json(
            new TransactionResource($transaction->load('client', 'gateway', 'products.product'))
        );
    }
}
