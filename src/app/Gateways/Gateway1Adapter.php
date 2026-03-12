<?php

namespace App\Gateways;

use App\Gateways\Contracts\GatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class Gateway1Adapter implements GatewayInterface
{
    private Client $http;
    private string $baseUrl;
    private string $email;
    private string $token;
    private int $cacheTtl;

    public function __construct(array $credentials)
    {
        $this->baseUrl  = rtrim($credentials['url'], '/');
        $this->email    = $credentials['email'];
        $this->token    = $credentials['token'];
        $this->cacheTtl = (int) config('gateways.token_cache_ttl', 3600);
        $this->http     = new Client(['base_uri' => $this->baseUrl, 'timeout' => 30]);
    }

    public function charge(array $payload): array
    {
        try {
            $response = $this->http->post('/transactions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getBearerToken(),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'amount'     => $payload['amount'],
                    'name'       => $payload['name'],
                    'email'      => $payload['email'],
                    'cardNumber' => $payload['cardNumber'],
                    'cvv'        => $payload['cvv'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'external_id' => (string) ($data['id'] ?? $data['transaction_id'] ?? ''),
                'status'      => 'approved',
            ];
        } catch (GuzzleException $e) {
            Log::error('Gateway Primário: falha na cobrança', ['error' => $e->getMessage()]);
            throw new RuntimeException('Gateway Primário falhou: ' . $e->getMessage(), 0, $e);
        }
    }

    public function refund(string $externalId): array
    {
        try {
            $response = $this->http->post("/transactions/{$externalId}/charge_back", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getBearerToken(),
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            Log::error('Gateway Primário: falha no reembolso', ['external_id' => $externalId, 'error' => $e->getMessage()]);
            throw new RuntimeException('Gateway Primário falhou ao reembolsar: ' . $e->getMessage(), 0, $e);
        }
    }

    private function getBearerToken(): string
    {
        $cacheKey = 'gateway1_bearer_token';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            try {
                $response = $this->http->post('/login', [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json'    => ['email' => $this->email, 'token' => $this->token],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                return $data['token'] ?? throw new RuntimeException('Gateway Primário: token não retornado no login.');
            } catch (GuzzleException $e) {
                throw new RuntimeException('Gateway Primário: falha no login: ' . $e->getMessage(), 0, $e);
            }
        });
    }
}
