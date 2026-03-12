<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_fazer_login_com_credenciais_validas(): void
    {
        $user = User::factory()->create([
            'email'    => 'admin@bet.com',
            'password' => bcrypt('password'),
            'role'     => UserRole::Admin,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'admin@bet.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
    }

    public function test_login_falha_com_credenciais_invalidas(): void
    {
        User::factory()->create([
            'email'    => 'admin@bet.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'admin@bet.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciais inválidas.']);
    }

    public function test_login_falha_com_email_inexistente(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'inexistente@bet.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_valida_campos_obrigatorios(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_resposta_nao_expoe_senha(): void
    {
        User::factory()->create([
            'email'    => 'admin@bet.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'admin@bet.com',
            'password' => 'password',
        ]);

        $response->assertJsonMissing(['password']);
    }
}
