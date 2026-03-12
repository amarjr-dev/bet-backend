<?php

namespace Tests\Feature\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $finance;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin       = User::factory()->create(['role' => UserRole::Admin]);
        $this->manager     = User::factory()->create(['role' => UserRole::Manager]);
        $this->finance     = User::factory()->create(['role' => UserRole::Finance]);
        $this->regularUser = User::factory()->create(['role' => UserRole::User]);
    }

    public function test_admin_pode_listar_usuarios(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/users')
            ->assertStatus(200);
    }

    public function test_finance_nao_pode_listar_usuarios(): void
    {
        $this->actingAs($this->finance, 'sanctum')
            ->getJson('/api/users')
            ->assertStatus(403);
    }

    public function test_admin_pode_criar_usuario(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/users', [
                'name'     => 'Novo Usuário',
                'email'    => 'novo@bet.com',
                'password' => 'password123',
                'role'     => 'user',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('email', 'novo@bet.com');
    }

    public function test_manager_nao_pode_criar_usuario_admin(): void
    {
        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/users', [
                'name'     => 'Fake Admin',
                'email'    => 'fakeadmin@bet.com',
                'password' => 'password123',
                'role'     => 'admin',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_admin_pode_atualizar_usuario(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/users/{$user->id}", ['name' => 'Novo Nome'])
            ->assertStatus(200)
            ->assertJsonPath('name', 'Novo Nome');
    }

    public function test_admin_pode_excluir_usuario(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/users/{$user->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_usuario_nao_pode_excluir_a_si_mesmo(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/users/{$this->admin->id}")
            ->assertStatus(422);
    }

    public function test_resposta_nao_expoe_senha(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/users')
            ->assertJsonMissing(['password']);
    }
}
