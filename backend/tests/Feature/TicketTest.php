<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

// ── Helpers ──────────────────────────────────────────────────────────────────

function actingAsRole(string $role): array
{
    $user  = User::factory()->create();
    $user->assignRole($role);
    $token = auth('api')->login($user);
    return [$user, $token];
}

function ticketPayload(array $overrides = []): array
{
    return array_merge([
        'title'       => 'Ticket de prueba',
        'description' => 'Descripción del incidente',
        'priority'    => 'medium',
    ], $overrides);
}

// ── Index ─────────────────────────────────────────────────────────────────────

test('admin ve todos los tickets', function () {
    [, $token] = actingAsRole('admin');
    Ticket::factory(5)->create();

    $this->withToken($token)->getJson('/api/tickets')
         ->assertOk()
         ->assertJsonPath('total', 5);
});

test('cliente solo ve sus propios tickets', function () {
    [$cliente, $token] = actingAsRole('cliente');
    Ticket::factory(3)->create(['creator_id' => $cliente->id]);
    Ticket::factory(2)->create();

    $this->withToken($token)->getJson('/api/tickets')
         ->assertOk()
         ->assertJsonPath('total', 3);
});

test('index requiere autenticación', function () {
    $this->getJson('/api/tickets')->assertStatus(401);
});

// ── Store ─────────────────────────────────────────────────────────────────────

test('agente puede crear un ticket', function () {
    [$agente, $token] = actingAsRole('agente');

    $this->withToken($token)
         ->postJson('/api/tickets', ticketPayload())
         ->assertStatus(201)
         ->assertJsonPath('title', 'Ticket de prueba')
         ->assertJsonPath('creator_id', $agente->id);
});

test('no se puede crear ticket con datos inválidos', function () {
    [, $token] = actingAsRole('agente');

    $this->withToken($token)
         ->postJson('/api/tickets', ['title' => ''])
         ->assertStatus(422);
});

// ── Show ──────────────────────────────────────────────────────────────────────

test('agente puede ver cualquier ticket', function () {
    [, $token] = actingAsRole('agente');
    $ticket = Ticket::factory()->create();

    $this->withToken($token)->getJson("/api/tickets/{$ticket->id}")
         ->assertOk()
         ->assertJsonPath('id', $ticket->id);
});

test('cliente no puede ver ticket ajeno', function () {
    [, $token] = actingAsRole('cliente');
    $ticket = Ticket::factory()->create();

    $this->withToken($token)->getJson("/api/tickets/{$ticket->id}")
         ->assertStatus(403);
});

// ── Update ────────────────────────────────────────────────────────────────────

test('agente puede actualizar un ticket', function () {
    [, $token] = actingAsRole('agente');
    $ticket = Ticket::factory()->create(['status' => 'open']);

    $this->withToken($token)
         ->putJson("/api/tickets/{$ticket->id}", ['status' => 'in_progress'])
         ->assertOk()
         ->assertJsonPath('status', 'in_progress');
});

test('cliente no puede actualizar tickets', function () {
    [, $token] = actingAsRole('cliente');
    $ticket = Ticket::factory()->create();

    $this->withToken($token)
         ->putJson("/api/tickets/{$ticket->id}", ['status' => 'closed'])
         ->assertStatus(403);
});

// ── Destroy ───────────────────────────────────────────────────────────────────

test('admin puede eliminar un ticket', function () {
    [, $token] = actingAsRole('admin');
    $ticket = Ticket::factory()->create();

    $this->withToken($token)->deleteJson("/api/tickets/{$ticket->id}")
         ->assertStatus(204);

    $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
});

test('agente no puede eliminar tickets', function () {
    [, $token] = actingAsRole('agente');
    $ticket = Ticket::factory()->create();

    $this->withToken($token)->deleteJson("/api/tickets/{$ticket->id}")
         ->assertStatus(403);
});

// ── Export ────────────────────────────────────────────────────────────────────

test('export xlsx devuelve archivo excel', function () {
    [, $token] = actingAsRole('admin');
    Ticket::factory(3)->create();

    $this->withToken($token)->get('/api/tickets/export?format=xlsx')
         ->assertOk()
         ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('export csv devuelve archivo csv', function () {
    [, $token] = actingAsRole('admin');
    Ticket::factory(2)->create();

    $this->withToken($token)->get('/api/tickets/export?format=csv')
         ->assertOk()
         ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('export respeta filtro de estado', function () {
    [, $token] = actingAsRole('admin');
    Ticket::factory(3)->create(['status' => 'open']);
    Ticket::factory(2)->create(['status' => 'closed']);

    $response = $this->withToken($token)->get('/api/tickets/export?format=csv&status=open');
    $response->assertOk();
    // el CSV tiene 3 filas de datos + 1 cabecera
    $lines = array_filter(explode("\n", $response->streamedContent()));
    expect(count($lines))->toBe(4);
});

test('export requiere autenticación', function () {
    $this->getJson('/api/tickets/export')->assertStatus(401);
});

test('cliente solo exporta sus propios tickets', function () {
    [$cliente, $token] = actingAsRole('cliente');
    Ticket::factory(2)->create(['creator_id' => $cliente->id]);
    Ticket::factory(3)->create();

    $response = $this->withToken($token)->get('/api/tickets/export?format=csv');
    $response->assertOk();
    $lines = array_filter(explode("\n", $response->streamedContent()));
    expect(count($lines))->toBe(3); // 2 tickets + 1 cabecera
});
