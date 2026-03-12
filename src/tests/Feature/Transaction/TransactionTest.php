<?php

namespace Tests\Feature\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $finance;
    private User $manager;
    private User $regularUser;
    private Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin       = User::factory()->create(['role' => UserRole::Admin]);
        $this->finance     = User::factory()->create(['role' => UserRole::Finance]);
        $this->manager     = User::factory()->create(['role' => UserRole::Manager]);
        $this->regularUser = User::factory()->create(['role' => UserRole::User]);

        $client  = Client::factory()->create();
        $gateway = Gateway::factory()->create();

        $this->transaction = Transaction::factory()->create([
            'client_id'  => $client->id,
            'gateway_id' => $gateway->id,
            'status'     => TransactionStatus::Approved,
            'external_id'=> 'ext_001',
        ]);
    }

    public function test_admin_pode_listar_transacoes(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/transactions')
            ->assertStatus(200);
    }

    public function test_finance_pode_listar_transacoes(): void
    {
        $this->actingAs($this->finance, 'sanctum')
            ->getJson('/api/transactions')
            ->assertStatus(200);
    }

    public function test_manager_nao_pode_listar_transacoes(): void
    {
        $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/transactions')
            ->assertStatus(403);
    }

    public function test_admin_pode_ver_detalhe_transacao(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/transactions/{$this->transaction->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'status', 'amount', 'card_last_numbers']);
    }

    public function test_admin_pode_realizar_reembolso(): void
    {
        $this->mock(PaymentGatewayService::class, function ($mock) {
            $mock->shouldReceive('refund')->once()->andReturn([]);
        });

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/transactions/{$this->transaction->id}/refund")
            ->assertStatus(200)
            ->assertJsonPath('status', 'refunded');
    }

    public function test_reembolso_falha_se_transacao_nao_esta_aprovada(): void
    {
        $this->transaction->update(['status' => TransactionStatus::Refunded]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/transactions/{$this->transaction->id}/refund")
            ->assertStatus(422);
    }

    public function test_user_nao_pode_fazer_reembolso(): void
    {
        $this->actingAs($this->regularUser, 'sanctum')
            ->postJson("/api/transactions/{$this->transaction->id}/refund")
            ->assertStatus(403);
    }
}
