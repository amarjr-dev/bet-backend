<?php

namespace Tests\Unit;

use App\Gateways\Gateway1Adapter;
use App\Gateways\Gateway2Adapter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class GatewayAdapterTest extends TestCase
{
    private function makeGuzzleMock(array $responses): Client
    {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        return new Client(['handler' => $handler]);
    }

    private function makeGateway1WithClient(Client $client): Gateway1Adapter
    {
        $credentials = [
            'url'   => 'http://gateway1.test',
            'email' => 'test@test.com',
            'token' => 'secret',
        ];
        $adapter = new Gateway1Adapter($credentials);
        // Injeta o client via reflection para testes
        $ref = new \ReflectionProperty($adapter, 'http');
        $ref->setAccessible(true);
        $ref->setValue($adapter, $client);
        return $adapter;
    }

    private function makeGateway2WithClient(Client $client): Gateway2Adapter
    {
        $credentials = [
            'url'         => 'http://gateway2.test',
            'auth_token'  => 'tok_123',
            'auth_secret' => 'sec_456',
        ];
        $adapter = new Gateway2Adapter($credentials);
        $ref = new \ReflectionProperty($adapter, 'http');
        $ref->setAccessible(true);
        $ref->setValue($adapter, $client);
        return $adapter;
    }

    // ---- Gateway 1 ----

    public function test_gateway1_charge_mapeia_campos_corretamente(): void
    {
        Cache::put('gateway1_bearer_token', 'cached_token', 3600);

        $guzzle = $this->makeGuzzleMock([
            new Response(200, [], json_encode(['id' => 'txn_abc'])),
        ]);

        $adapter = $this->makeGateway1WithClient($guzzle);

        $result = $adapter->charge([
            'amount'     => 9900,
            'name'       => 'John Doe',
            'email'      => 'john@test.com',
            'cardNumber' => '4111111111111111',
            'cvv'        => '123',
        ]);

        $this->assertSame('txn_abc', $result['external_id']);
        $this->assertSame('approved', $result['status']);
    }

    public function test_gateway1_charge_lanca_excecao_em_falha_http(): void
    {
        $this->expectException(RuntimeException::class);

        Cache::put('gateway1_bearer_token', 'cached_token', 3600);

        $guzzle = $this->makeGuzzleMock([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $adapter = $this->makeGateway1WithClient($guzzle);
        $adapter->charge([
            'amount'     => 9900,
            'name'       => 'John Doe',
            'email'      => 'john@test.com',
            'cardNumber' => '4111111111111111',
            'cvv'        => '123',
        ]);
    }

    public function test_gateway1_reutiliza_token_em_cache(): void
    {
        Cache::put('gateway1_bearer_token', 'already_cached', 3600);

        // Apenas uma resposta (charge) — não há chamada ao /login pois o token está em cache
        $guzzle = $this->makeGuzzleMock([
            new Response(200, [], json_encode(['id' => 'txn_xyz'])),
        ]);

        $adapter = $this->makeGateway1WithClient($guzzle);
        $result  = $adapter->charge([
            'amount'     => 1000,
            'name'       => 'Jane',
            'email'      => 'jane@test.com',
            'cardNumber' => '4111111111111111',
            'cvv'        => '321',
        ]);

        $this->assertSame('txn_xyz', $result['external_id']);
    }

    public function test_gateway1_refund_chama_endpoint_correto(): void
    {
        Cache::put('gateway1_bearer_token', 'cached_token', 3600);

        $guzzle = $this->makeGuzzleMock([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $adapter = $this->makeGateway1WithClient($guzzle);
        $result  = $adapter->refund('ext_001');

        $this->assertTrue($result['success']);
    }

    // ---- Gateway 2 ----

    public function test_gateway2_charge_mapeia_campos_para_portugues(): void
    {
        $capturedRequest = null;

        $mock    = new MockHandler([
            new Response(200, [], json_encode(['id' => 'gt2_abc'])),
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push(function (callable $next) use (&$capturedRequest) {
            return function ($request, $options) use ($next, &$capturedRequest) {
                $capturedRequest = $request;
                return $next($request, $options);
            };
        });
        $guzzle = new Client(['handler' => $handler]);

        $adapter = $this->makeGateway2WithClient($guzzle);
        $result  = $adapter->charge([
            'amount'     => 5000,
            'name'       => 'Maria',
            'email'      => 'maria@test.com',
            'cardNumber' => '4111111111111111',
            'cvv'        => '456',
        ]);

        $this->assertSame('gt2_abc', $result['external_id']);
        $this->assertSame('approved', $result['status']);

        $body = json_decode($capturedRequest->getBody()->getContents(), true);
        $this->assertArrayHasKey('valor', $body);
        $this->assertArrayHasKey('nome', $body);
        $this->assertArrayHasKey('email', $body);
        $this->assertArrayHasKey('numeroCartao', $body);
        $this->assertArrayHasKey('cvv', $body);
    }

    public function test_gateway2_refund_chama_endpoint_correto(): void
    {
        $guzzle = $this->makeGuzzleMock([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $adapter = $this->makeGateway2WithClient($guzzle);
        $result  = $adapter->refund('gt2_ext_999');

        $this->assertTrue($result['success']);
    }

    public function test_gateway2_charge_lanca_excecao_em_falha(): void
    {
        $this->expectException(RuntimeException::class);

        $guzzle = $this->makeGuzzleMock([
            new Response(503, [], 'Service Unavailable'),
        ]);

        $adapter = $this->makeGateway2WithClient($guzzle);
        $adapter->charge([
            'amount'     => 5000,
            'name'       => 'Test',
            'email'      => 'test@test.com',
            'cardNumber' => '4111111111111111',
            'cvv'        => '000',
        ]);
    }
}
