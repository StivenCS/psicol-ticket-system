<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

test('usuario puede iniciar sesión con credenciales válidas', function () {
    $user = User::factory()->create(['password' => 'secret123']);
    $user->assignRole('agente');

    $response = $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertOk()
             ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
});

test('login falla con contraseña incorrecta', function () {
    $user = User::factory()->create(['password' => 'correct']);

    $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'wrong',
    ])->assertStatus(401)->assertJson(['message' => 'Credenciales inválidas']);
});

test('login falla sin campos requeridos', function () {
    $this->postJson('/api/auth/login', [])->assertStatus(422);
});

test('me retorna usuario autenticado con roles', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $token = auth('api')->login($user);

    $this->withToken($token)
         ->getJson('/api/auth/me')
         ->assertOk()
         ->assertJsonPath('email', $user->email)
         ->assertJsonStructure(['roles']);
});

test('logout invalida el token', function () {
    $user  = User::factory()->create();
    $token = auth('api')->login($user);

    $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
});

test('endpoint protegido requiere autenticación', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);
});
