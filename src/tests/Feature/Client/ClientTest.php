<?php

namespace Tests\Feature\Client;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $finance;
    private User $regularUser;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin       = User::factory()->create(['role' => UserRole::Admin]);
        $this->finance     = User::factory()->create(['role' => UserRole::Finance]);
        $this->regularUser = User::factory()->create(['role' => UserRole::User]);
        $this->client      = Client::factory()->create();
    }

    public function test_admin_pode_listar_clientes(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/clients')
            ->assertStatus(200);
    }

    public function test_finance_pode_listar_clientes(): void
    {
        $this->actingAs($this->finance, 'sanctum')
            ->getJson('/api/clients')
            ->assertStatus(200);
    }

    public function test_user_nao_pode_listar_clientes(): void
    {
        $this->actingAs($this->regularUser, 'sanctum')
            ->getJson('/api/clients')
            ->assertStatus(403);
    }

    public function test_admin_pode_ver_detalhe_do_cliente(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/clients/{$this->client->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'email', 'transactions']);
    }

    public function test_cliente_inexistente_retorna_404(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/clients/9999')
            ->assertStatus(404);
    }
}
