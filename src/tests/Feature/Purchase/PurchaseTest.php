<?php

namespace Tests\Feature\Purchase;

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create(['amount' => 2990]);

        $this->validPayload = [
            'client' => [
                'name'       => 'João Silva',
                'email'      => 'joao@email.com',
                'cardNumber' => '5569000000006063',
                'cvv'        => '010',
            ],
            'products' => [
                ['id' => $this->product->id, 'quantity' => 2],
            ],
        ];
    }

    public function test_compra_realizada_com_sucesso(): void
    {
        Gateway::factory()->create(['driver' => 'Gateway1Adapter', 'is_active' => true, 'priority' => 1]);

        $this->mock(PaymentGatewayService::class, function ($mock) {
            $gateway = Gateway::first();
            $mock->shouldReceive('charge')->once()->andReturn([
                'external_id' => 'ext_123',
                'status'      => 'approved',
                'gateway'     => $gateway,
            ]);
        });

        $response = $this->postJson('/api/purchases', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'status', 'amount', 'card_last_numbers', 'external_id',
                'client' => ['id', 'name', 'email'],
                'gateway' => ['id', 'name'],
                'products',
            ]);

        $this->assertEquals('approved', $response->json('status'));
        $this->assertEquals(5980, $response->json('amount')); // 2990 * 2
        $this->assertEquals('6063', $response->json('card_last_numbers'));
    }

    public function test_compra_usa_gateway2_quando_gateway1_falha(): void
    {
        $gw1 = Gateway::factory()->create(['driver' => 'Gateway1Adapter', 'is_active' => true, 'priority' => 1]);
        $gw2 = Gateway::factory()->create(['driver' => 'Gateway2Adapter', 'is_active' => true, 'priority' => 2]);

        $this->mock(PaymentGatewayService::class, function ($mock) use ($gw2) {
            $mock->shouldReceive('charge')->once()->andReturn([
                'external_id' => 'ext_456',
                'status'      => 'approved',
                'gateway'     => $gw2,
            ]);
        });

        $response = $this->postJson('/api/purchases', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonPath('gateway.id', $gw2->id);
    }

    public function test_compra_falha_quando_todos_gateways_falham(): void
    {
        $this->mock(PaymentGatewayService::class, function ($mock) {
            $mock->shouldReceive('charge')->once()->andThrow(new \RuntimeException('Todos os gateways falharam.'));
        });

        $response = $this->postJson('/api/purchases', $this->validPayload);

        $response->assertStatus(500);
    }

    public function test_compra_rejeita_payload_invalido(): void
    {
        $response = $this->postJson('/api/purchases', [
            'client' => ['name' => 'Teste'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client.email', 'client.cardNumber', 'client.cvv', 'products']);
    }

    public function test_compra_rejeita_numero_cartao_invalido(): void
    {
        $payload = $this->validPayload;
        $payload['client']['cardNumber'] = '1234'; // menos de 16 dígitos

        $response = $this->postJson('/api/purchases', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client.cardNumber']);
    }

    public function test_calculo_do_total_e_feito_no_backend(): void
    {
        Gateway::factory()->create(['driver' => 'Gateway1Adapter', 'is_active' => true, 'priority' => 1]);

        $this->mock(PaymentGatewayService::class, function ($mock) {
            $gateway = Gateway::first();
            $mock->shouldReceive('charge')
                ->once()
                ->with(\Mockery::on(fn($p) => $p['amount'] === 5980)) // 2990 * 2, não do cliente
                ->andReturn(['external_id' => 'ext_789', 'status' => 'approved', 'gateway' => $gateway]);
        });

        $this->postJson('/api/purchases', $this->validPayload)->assertStatus(201);
    }

    public function test_compra_rejeita_produto_soft_deletado(): void
    {
        $this->product->delete(); // soft-delete

        $response = $this->postJson('/api/purchases', $this->validPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['products.0.id']);
    }
}
