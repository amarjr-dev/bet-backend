<?php

namespace App\Gateways;

use App\Gateways\Contracts\GatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class Gateway2Adapter implements GatewayInterface
{
    private Client $http;

    public function __construct(array $credentials)
    {
        $baseUrl = rtrim($credentials['url'], '/');

        $this->http = new Client([
            'base_uri' => $baseUrl,
            'timeout'  => 30,
            'headers'  => [
                'Gateway-Auth-Token'  => $credentials['auth_token'],
                'Gateway-Auth-Secret' => $credentials['auth_secret'],
                'Content-Type'        => 'application/json',
            ],
        ]);
    }

    public function charge(array $payload): array
    {
        try {
            $response = $this->http->post('/transacoes', [
                'json' => [
                    'valor'        => $payload['amount'],
                    'nome'         => $payload['name'],
                    'email'        => $payload['email'],
                    'numeroCartao' => $payload['cardNumber'],
                    'cvv'          => $payload['cvv'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'external_id' => (string) ($data['id'] ?? $data['transaction_id'] ?? ''),
                'status'      => 'approved',
            ];
        } catch (GuzzleException $e) {
            Log::error('Gateway Secundário: falha na cobrança', ['error' => $e->getMessage()]);
            throw new RuntimeException('Gateway Secundário falhou: ' . $e->getMessage(), 0, $e);
        }
    }

    public function refund(string $externalId): array
    {
        try {
            $response = $this->http->post('/transacoes/reembolso', [
                'json' => ['id' => $externalId],
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            Log::error('Gateway Secundário: falha no reembolso', ['external_id' => $externalId, 'error' => $e->getMessage()]);
            throw new RuntimeException('Gateway Secundário falhou ao reembolsar: ' . $e->getMessage(), 0, $e);
        }
    }
}
