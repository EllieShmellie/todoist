<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_require_a_bearer_token(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->getJson('/api/tasks')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_protected_api_route_returns_json_401_when_client_accepts_html(): void
    {
        $this->get('/api/user', ['Accept' => 'text/html'])
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_login_input_is_validated_as_json(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => ['email', 'password'],
            ]);
    }

    public function test_invalid_credentials_return_a_json_401(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    public function test_user_can_login_and_fetch_their_profile_with_the_token(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => ' ADMIN@example.com ',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonStructure(['token', 'token_type', 'user']);

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', 'admin@example.com')
            ->assertJsonPath('role', 'admin');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $currentToken = $user->createToken('current')->plainTextToken;
        $user->createToken('another-device');

        $this->withToken($currentToken)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertExactJson(['message' => 'Logged out successfully.']);

        $this->assertCount(1, $user->tokens()->get());

        // Each real HTTP request gets a fresh guard instance; reset the cached
        // guard here to model the next request inside Laravel's test kernel.
        Auth::forgetGuards();

        $this->withToken($currentToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }
}
