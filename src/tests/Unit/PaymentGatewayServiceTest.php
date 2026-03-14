<?php

namespace Tests\Unit;

use App\Gateways\Contracts\GatewayInterface;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PaymentGatewayServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria uma instância de PaymentGatewayService que substitui resolveAdapter
     * por um closure, permitindo injetar adapters mockados nos testes.
     */
    private function makeServiceWithResolver(callable $resolver): PaymentGatewayService
    {
        return new class ($resolver) extends PaymentGatewayService {
            public function __construct(private readonly \Closure $resolver)
            {
            }

            protected function resolveAdapter(Gateway $gateway): GatewayInterface
            {
                return ($this->resolver)($gateway);
            }
        };
    }

    public function test_charge_usa_gateway_de_maior_prioridade(): void
    {
        $gateway = Gateway::factory()->create(['priority' => 1, 'is_active' => true]);

        $adapter = Mockery::mock(GatewayInterface::class);
        $adapter->shouldReceive('charge')
            ->once()
            ->andReturn(['external_id' => 'ext_123', 'status' => 'approved']);

        $service = $this->makeServiceWithResolver(fn () => $adapter);
        $result  = $service->charge([
            'amount'     => 5000,
            'name'       => 'Test User',
            'email'      => 'test@test.com',
            'cardNumber' => '1234567890123456',
            'cvv'        => '123',
        ]);

        $this->assertSame('ext_123', $result['external_id']);
        $this->assertSame($gateway->id, $result['gateway']->id);
    }

    public function test_charge_faz_failover_para_proximo_gateway(): void
    {
        $gw1 = Gateway::factory()->create(['priority' => 1, 'is_active' => true]);
        $gw2 = Gateway::factory()->create(['priority' => 2, 'is_active' => true]);

        $failAdapter = Mockery::mock(GatewayInterface::class);
        $failAdapter->shouldReceive('charge')->once()->andThrow(new RuntimeException('Gateway 1 falhou'));

        $successAdapter = Mockery::mock(GatewayInterface::class);
        $successAdapter->shouldReceive('charge')
            ->once()
            ->andReturn(['external_id' => 'ext_456', 'status' => 'approved']);

        $service = $this->makeServiceWithResolver(
            fn (Gateway $g) => $g->id === $gw1->id ? $failAdapter : $successAdapter
        );

        $result = $service->charge([
            'amount'     => 5000,
            'name'       => 'Test User',
            'email'      => 'test@test.com',
            'cardNumber' => '1234567890123456',
            'cvv'        => '123',
        ]);

        $this->assertSame('ext_456', $result['external_id']);
        $this->assertSame($gw2->id, $result['gateway']->id);
    }

    public function test_charge_lanca_excecao_se_todos_falharem(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Todos os gateways falharam/');

        Gateway::factory()->create(['priority' => 1, 'is_active' => true]);

        $failAdapter = Mockery::mock(GatewayInterface::class);
        $failAdapter->shouldReceive('charge')->once()->andThrow(new RuntimeException('Falhou'));

        $service = $this->makeServiceWithResolver(fn () => $failAdapter);
        $service->charge([
            'amount'     => 5000,
            'name'       => 'Test User',
            'email'      => 'test@test.com',
            'cardNumber' => '1234567890123456',
            'cvv'        => '123',
        ]);
    }

    public function test_charge_lanca_excecao_sem_gateways_ativos(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/gateway de pagamento/');

        $service = new PaymentGatewayService();
        $service->charge([
            'amount'     => 5000,
            'name'       => 'Test',
            'email'      => 'test@test.com',
            'cardNumber' => '1234567890123456',
            'cvv'        => '123',
        ]);
    }

    public function test_refund_usa_gateway_da_transacao(): void
    {
        $gateway     = Gateway::factory()->create();
        $transaction = Transaction::factory()->create([
            'gateway_id'  => $gateway->id,
            'external_id' => 'ext_789',
        ]);

        $adapter = Mockery::mock(GatewayInterface::class);
        $adapter->shouldReceive('refund')->with('ext_789')->once()->andReturn(['success' => true]);

        $service = $this->makeServiceWithResolver(fn () => $adapter);
        $result  = $service->refund($transaction);

        $this->assertTrue($result['success']);
    }
}
