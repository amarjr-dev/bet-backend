<?php

namespace Tests\Feature\Gateway;

use App\Enums\UserRole;
use App\Models\Gateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private Gateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin   = User::factory()->create(['role' => UserRole::Admin]);
        $this->manager = User::factory()->create(['role' => UserRole::Manager]);
        $this->gateway = Gateway::factory()->create(['is_active' => true, 'priority' => 1]);
    }

    public function test_admin_pode_listar_gateways(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/gateways')
            ->assertStatus(200);
    }

    public function test_nao_admin_nao_pode_listar_gateways(): void
    {
        $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/gateways')
            ->assertStatus(403);
    }

    public function test_admin_pode_toggle_gateway(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/gateways/{$this->gateway->id}/toggle")
            ->assertStatus(200)
            ->assertJsonPath('is_active', false);
    }

    public function test_admin_pode_alterar_prioridade(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/gateways/{$this->gateway->id}/priority", ['priority' => 3])
            ->assertStatus(200)
            ->assertJsonPath('priority', 3);
    }

    public function test_prioridade_invalida_retorna_erro(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/gateways/{$this->gateway->id}/priority", ['priority' => 0])
            ->assertStatus(422);
    }

    public function test_credenciais_nao_aparecem_na_resposta(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/gateways')
            ->assertJsonMissing(['credentials']);
    }
}
