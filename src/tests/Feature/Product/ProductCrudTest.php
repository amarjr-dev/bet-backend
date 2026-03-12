<?php

namespace Tests\Feature\Product;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $finance;
    private User $regularUser;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin       = User::factory()->create(['role' => UserRole::Admin]);
        $this->manager     = User::factory()->create(['role' => UserRole::Manager]);
        $this->finance     = User::factory()->create(['role' => UserRole::Finance]);
        $this->regularUser = User::factory()->create(['role' => UserRole::User]);
        $this->product     = Product::factory()->create(['name' => 'Produto Teste', 'amount' => 1990]);
    }

    public function test_todos_autenticados_podem_listar_produtos(): void
    {
        foreach ([$this->admin, $this->manager, $this->finance, $this->regularUser] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/products')
                ->assertStatus(200);
        }
    }

    public function test_nao_autenticado_nao_pode_listar_produtos(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    public function test_admin_pode_criar_produto(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/products', ['name' => 'Novo Produto', 'amount' => 4990])
            ->assertStatus(201)
            ->assertJsonPath('amount', 4990);
    }

    public function test_finance_pode_criar_produto(): void
    {
        $this->actingAs($this->finance, 'sanctum')
            ->postJson('/api/products', ['name' => 'Produto Finance', 'amount' => 1000])
            ->assertStatus(201);
    }

    public function test_user_nao_pode_criar_produto(): void
    {
        $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/products', ['name' => 'Tentativa', 'amount' => 1000])
            ->assertStatus(403);
    }

    public function test_admin_pode_excluir_produto(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/products/{$this->product->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('products', ['id' => $this->product->id]);
    }

    public function test_finance_nao_pode_excluir_produto(): void
    {
        $this->actingAs($this->finance, 'sanctum')
            ->deleteJson("/api/products/{$this->product->id}")
            ->assertStatus(403);
    }
}
